<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\SlotMachine\SlotMachineJackpot;
use App\Entity\User\User;
use App\Repository\BankAccountRepository;
use App\Repository\HonorRepository;
use App\Repository\SlotMachineJackpotRepository;
use App\Repository\UserRepository;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnexpectedResultException;

readonly class HonorService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private HonorRepository $honorRepository,
        private BankAccountRepository $bankAccountRepository,
        private SlotMachineJackpotRepository $slotMachineJackpotRepository,
        private UserRepository $userRepository,
    ) {

    }

    public function getLeaderboardByChat(Chat $chat): ?string
    {
        $leaderboard = $this->honorRepository->getLeaderboard($chat);
        if (count($leaderboard) === 0) {
            return null;
        } else {
            $text = array_map(function ($entry) use ($chat) {
                $honor = $entry['amount'];
                $user = $this->userRepository->find($entry['id']);
                if ($user !== null) {
                    $balance = $this->bankAccountRepository->getByChatAndUser($chat, $user)?->getBalance();
                } else {
                    $balance = null;
                }
                $text = <<<TEXT
                <strong>%s</strong>
                %s Ehre
                %s Bank
                TEXT;
                return sprintf(
                    $text,
                    $user->getName() ?? $user->getFirstName(),
                    NumberFormat::format($honor),
                    NumberFormat::format($balance ?? 0),
                );
            }, $leaderboard);
            return implode(PHP_EOL, $text);
        }
    }

    public function getCurrentHonorAmount(Chat $chat, User $user): int
    {
        try {
            return $this->honorRepository->getHonorCount($user, $chat);
        } catch (UnexpectedResultException $exception) {
            throw new \RuntimeException('failed to load honor count', previous: $exception);
        }
    }

    public function addHonor(Chat $chat, User $recipient, int $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, abs($amount)));
    }

    public function removeHonor(Chat $chat, User $recipient, int $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, -abs($amount)));
    }

    public function getSlotMachineJackpot(Chat $chat): SlotMachineJackpot
    {
        $jackpot = $this->slotMachineJackpotRepository->findOneBy(['chat' => $chat]);
        if ($jackpot === null) {
            $jackpot = new SlotMachineJackpot();
            $jackpot->setChat($chat);
            $jackpot->setAmount(0);
            $jackpot->setActive(true);
            $this->manager->persist($jackpot);
        }
        return $jackpot;
    }

}
