<?php declare(strict_types=1);

namespace App\Service\Honor;

use App\Dto\RaidResult;
use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\Raid\Raid;
use App\Entity\Honor\Raid\RaidFactory;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\User\User;
use App\Exception\RaidGuardException;
use App\Repository\RaidRepository;
use App\Service\Items\ItemEffectService;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RaidService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private TranslatorInterface $translator,
        private HonorService $honorService,
        private ItemEffectService $effectService,
        private RaidRepository $raidRepository,
    ) {

    }

    public function getActiveRaid(Chat $chat): Raid
    {
        $raid = $this->raidRepository->getActiveRaid($chat);
        if ($raid === null) {
            throw new \RuntimeException('no active raid');
        }
        return $raid;
    }

    /**
     * @throws RaidGuardException
     */
    public function createRaid(Chat $chat, User $leader, User $target): Raid
    {
        if ($this->raidRepository->hasActiveRaid($chat)) {
            throw new \RuntimeException('raid already active');
        }
        $latestRaid = $this->raidRepository->getLatestRaidByLeader($chat, $leader);
        if ($latestRaid !== null) {
            $diff = time() - $latestRaid->getCreatedAt()->getTimestamp();
            if ($diff < 3600) {
                throw new \RuntimeException(sprintf('please wait %d minutes', 60 - ($diff / 60)));
            }
        }
        $targetHonorCount = $this->honorService->getCurrentHonorAmount($chat, $target);
        if ($targetHonorCount->lessThanOrEqual(Honor::currency(0))) {
            throw new \RuntimeException('target has no honor, no raid possible');
        }
        if ($this->hasRaidGuard($target, $chat)) {
            $guards = $this->getRaidGuards($target, $chat);
            $chance = $guards->apply('50');
            if (Random::getPercentChance((int) $chance)) {
                throw new RaidGuardException($leader, $target);
            }
        }
        $raidAmount = $this->getRaidAmount($targetHonorCount);
        $raid = RaidFactory::create($chat, $leader, $target, $raidAmount);
        $this->manager->persist($raid);
        $this->manager->flush();
        return $raid;
    }

    public function supportRaid(Chat $chat, User $supporter): Raid
    {
        $raid = $this->getActiveRaid($chat);
        if ($raid->getTarget()->getTelegramUserId() === $supporter->getTelegramUserId()) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.cannotSupportOwnRaid'));
        }
        if ($raid->getLeader()->getTelegramUserId() === $supporter->getTelegramUserId()) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.raidLeaderAutomaticallySupportsRaid'));
        }
        if ($raid->getSupporters()->filter(fn (User $user) => $user->getTelegramUserId() === $supporter->getTelegramUserId())->count() > 0) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.alreadySupportRaid'));
        }
        if ($raid->getDefenders()->filter(fn (User $user) => $user->getTelegramUserId() === $supporter->getTelegramUserId())->count() > 0) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.cannotSupportAndDefend'));
        }
        $raid->getSupporters()->add($supporter);
        $this->manager->persist($raid);
        $this->manager->flush();
        return $raid;
    }

    public function defendRaid(Chat $chat, User $defender): Raid
    {
        $raid = $this->getActiveRaid($chat);
        if ($raid->getTarget()->getTelegramUserId() === $defender->getTelegramUserId()) {
            throw new \RuntimeException('you cannot defend your own raid');
        }
        if ($raid->getLeader()->getTelegramUserId() === $defender->getTelegramUserId()) {
            throw new \RuntimeException('the raid leader cannot defend the raid');
        }
        if ($raid->getDefenders()->filter(fn (User $user) => $user->getTelegramUserId() === $defender->getTelegramUserId())->count() > 0) {
            throw new \RuntimeException('you already defend the raid');
        }
        if ($raid->getSupporters()->filter(fn (User $user) => $user->getTelegramUserId() === $defender->getTelegramUserId())->count() > 0) {
            throw new \RuntimeException('you cannot support and defend the raid');
        }
        $raid->getDefenders()->add($defender);
        $this->manager->persist($raid);
        $this->manager->flush();
        return $raid;
    }

    public function cancelRaid(Chat $chat, User $user): Raid
    {
        $raid = $this->getActiveRaid($chat);
        if ($raid->getLeader()->getTelegramUserId() !== $user->getTelegramUserId()) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.notLeaderError'));
        }
        $this->manager->remove($raid);
        $this->manager->flush();
        return $raid;
    }

    /**
     * returns true if the raid was successful, false if the raid failed
     */
    public function executeRaid(Chat $chat, User $user): RaidResult
    {
        $raid = $this->getActiveRaid($chat);
        if ($raid->getLeader()->getTelegramUserId() !== $user->getTelegramUserId()) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.noLeaderError'));
        }
        $supporterCount = $raid->getSupporters()->count();
        $defenderCount = $raid->getDefenders()->count();
        if ($supporterCount + $defenderCount === 0) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.noSupportersOrDefendersError'));
        }
        if ($this->isSuccessful($raid)) {
            return $this->success($raid);
        } else {
            return $this->fail($raid);
        }
    }

    private function isSuccessful(Raid $raid): bool
    {
        $leaderEffects = $this->effectService->getEffectsByUserAndType($raid->getLeader(), $raid->getChat(), [
            EffectType::OFFENSIVE_RAID_SUCCESS,
        ]);
        $successChance = $leaderEffects->apply('50');
        $targetEffects = $this->effectService->getEffectsByUserAndType($raid->getTarget(), $raid->getChat(), [
            EffectType::DEFENSIVE_RAID_SUCCESS,
        ]);
        $successChance = $targetEffects->apply($successChance);
        if (bccomp($successChance, '0') <= 0) {
            return false;
        }
        if (bccomp($successChance, '100') >= 0) {
            return true;
        }
        return Random::getPercentChance((int) $successChance);
    }

    private function success(Raid $raid): RaidResult
    {
        $raid->setIsActive(false);
        $raid->setIsSuccessful(true);
        $amount = $raid->getAmount();
        for ($i = 0; $i < $raid->getSupporters()->count(); $i++) {
            $amount = $amount->multiply('1.2'); // 20% more for each supporter
        }
        for ($i = 0; $i < $raid->getDefenders()->count(); $i++) {
            $amount = $amount->multiply('0.75'); // 25% less for each defender
        }
        $numberOfSupporters = $raid->getSupporters()->count() + 1;
        $honorPerSupporter = $amount->divide($numberOfSupporters);
        // add honor to leader
        $this->honorService->addHonor($raid->getChat(), $raid->getLeader(), $honorPerSupporter);
        // add honor to supporters
        foreach ($raid->getSupporters() as $supporter) {
            $this->honorService->addHonor($raid->getChat(), $supporter, $honorPerSupporter);
        }
        // remove honor from target
        $this->honorService->removeHonor($raid->getChat(), $raid->getTarget(), $raid->getAmount());
        $this->manager->flush();
        return new RaidResult($raid, true);
    }

    private function fail(Raid $raid): RaidResult
    {
        $raid->setIsActive(false);
        $raid->setIsSuccessful(false);
        $this->manager->flush();
        return new RaidResult($raid, false);
    }

    private function getRaidAmount(Money $targetHonor): Money
    {
        $raidAmount = $targetHonor->divide(2);
        if ($raidAmount->greaterThan(Honor::currency(100))) {
            return $raidAmount;
        }
        return Honor::currency(100);
    }

    public function hasRaidGuard(User $user, Chat $chat): bool
    {
        return $this->getRaidGuards($user, $chat)->count() > 0;
    }

    public function getRaidGuards(User $user, Chat $chat): EffectCollection
    {
        return $this->effectService->getEffectsByUserAndType($user, $chat, [
            EffectType::RAID_GUARD,
        ]);
    }

}
