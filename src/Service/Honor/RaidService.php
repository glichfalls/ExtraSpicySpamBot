<?php

namespace App\Service\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\Raid\Raid;
use App\Entity\Honor\Raid\RaidFactory;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\User\User;
use App\Repository\RaidRepository;
use App\Service\Items\ItemEffectService;
use App\Service\UserService;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

final class RaidService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private HonorService $honorService,
        private ItemEffectService $effectService,
        private UserService $userService,
        private RaidRepository $raidRepository,
    ) {

    }

    public function createRaid(
        Chat $chat,
        User $leader,
        User $target,
    ): Raid
    {
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
            $chance = $this->getRaidGuards($target, $chat)->apply(50);
            if (Random::getPercentChance($chance)) {
                // TODO: somehow notify the user that the raid guard protected them
                $leader = $target;
                $target = $leader;
                $targetHonorCount = $this->honorService->getCurrentHonorAmount($chat, $target);
                if ($targetHonorCount < 10_000) {
                    $targetHonorCount = 10_000;
                }
            }
        }
        $raidAmount = $this->getRaidAmount($targetHonorCount);
        $raid = RaidFactory::create($chat, $leader, $target, $raidAmount);
        $this->manager->persist($raid);
        $this->manager->flush();
        return $raid;
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