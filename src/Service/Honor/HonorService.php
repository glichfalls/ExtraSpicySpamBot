<?php declare(strict_types=1);

namespace App\Service\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\Season\Season;
use App\Entity\Honor\SlotMachine\SlotMachineJackpot;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Repository\SlotMachineJackpotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnexpectedResultException;
use Money\Money;

final readonly class HonorService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private HonorRepository $honorRepository,
        private SlotMachineJackpotRepository $slotMachineJackpotRepository,
        private SeasonService $seasonService,
    ) {

    }

    public function getHonorLeaderboardByChat(Chat $chat, ?Season $season = null): array
    {
        $season = $season ?? $this->seasonService->getSeason();
        return $this->honorRepository->getLeaderboard($season, $chat);
    }

    public function getCurrentHonorAmount(Chat $chat, User $user): Money
    {
        try {
            $season = $this->seasonService->getSeason();
            return $this->honorRepository->getHonorCount($season, $user, $chat);
        } catch (UnexpectedResultException $exception) {
            throw new \RuntimeException('failed to load honor count', previous: $exception);
        }
    }

    public function addHonor(Chat $chat, User $recipient, Money $amount, ?User $sender = null): void
    {
        $season = $this->seasonService->getSeason();
        $honor = HonorFactory::create($season, $chat, $sender, $recipient, $amount->absolute());
        $this->manager->persist($honor);
    }

    public function removeHonor(Chat $chat, User $recipient, Money $amount, ?User $sender = null): void
    {
        $season = $this->seasonService->getSeason();
        $honor = HonorFactory::create($season, $chat, $sender, $recipient, $amount->absolute()->negative());
        $this->manager->persist($honor);
    }

    public function getSlotMachineJackpot(Chat $chat): SlotMachineJackpot
    {
        $jackpot = $this->slotMachineJackpotRepository->findOneBy(['chat' => $chat]);
        if ($jackpot === null) {
            $jackpot = new SlotMachineJackpot();
            $jackpot->setChat($chat);
            $jackpot->setAmount(Honor::currency(0));
            $jackpot->setActive(true);
            $this->manager->persist($jackpot);
        }
        return $jackpot;
    }

}
