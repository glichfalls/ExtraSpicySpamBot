<?php declare(strict_types=1);

namespace App\Service\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\SlotMachine\SlotMachineJackpot;
use App\Entity\User\User;
use App\Repository\BankAccountRepository;
use App\Repository\HonorRepository;
use App\Repository\SlotMachineJackpotRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnexpectedResultException;
use Money\Money;
use Psr\Log\LoggerInterface;

final readonly class HonorService
{

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $manager,
        private HonorRepository $honorRepository,
        private BankAccountRepository $bankAccountRepository,
        private SlotMachineJackpotRepository $slotMachineJackpotRepository,
        private UserRepository $userRepository,
    ) {

    }

    public function getHonorLeaderboardByChat(Chat $chat): array
    {
        return $this->honorRepository->getLeaderboard($chat);
    }

    public function getCurrentHonorAmount(Chat $chat, User $user): Money
    {
        try {
            return $this->honorRepository->getHonorCount($user, $chat);
        } catch (UnexpectedResultException $exception) {
            throw new \RuntimeException('failed to load honor count', previous: $exception);
        }
    }

    public function addHonor(Chat $chat, User $recipient, Money $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, $amount->absolute()));
    }

    public function removeHonor(Chat $chat, User $recipient, Money $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, $amount->absolute()->negative()));
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
