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
use App\Service\Stocks\StockService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnexpectedResultException;
use Psr\Log\LoggerInterface;

readonly class HonorService
{

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $manager,
        private HonorRepository $honorRepository,
        private BankAccountRepository $bankAccountRepository,
        private SlotMachineJackpotRepository $slotMachineJackpotRepository,
        private UserRepository $userRepository,
        private StockService $stockService,
    ) {

    }

    public function getLeaderboardByChat(Chat $chat): ?string
    {
        $leaderboard = $this->honorRepository->getLeaderboard($chat);
        if (count($leaderboard) === 0) {
            return null;
        } else {
            // fetch user and balance
            foreach ($leaderboard as $key => $entry) {
                $user = $this->userRepository->find($entry['id']);
                $leaderboard[$key]['user'] = $user;
                $balance = $this->bankAccountRepository->getByChatAndUser($chat, $user)?->getBalance();
                $leaderboard[$key]['balance'] = $balance;
                $portfolio = $this->stockService->getPortfolioByUserAndChat($chat, $user);
                try {
                    $portfolioValue = $this->stockService->getPortfolioBalance($portfolio);
                } catch (\Exception $exception) {
                    $portfolioValue = 0;
                    $this->logger->error('failed to get portfolio balance', [
                        'exception' => $exception,
                        'chat' => $chat->getId(),
                        'user' => $user->getId(),
                    ]);
                }
                $leaderboard[$key]['portfolio'] = $portfolioValue;
                $leaderboard[$key]['total'] = $entry['amount'] + $balance + $portfolioValue;
            }
            // sort by total
            usort($leaderboard, fn ($a, $b) => $b['total'] <=> $a['total']);
            // format text
            $text = array_map(function ($entry) use ($chat) {
                $honor = $entry['amount'];
                $balance = $entry['balance'];
                $portfolioValue = $entry['portfolio'];
                $user = $entry['user'];
                $text = <<<TEXT
                [ <code>%s</code> | <code>%s</code> | <code>%s</code> ] <b>%s</b>
                TEXT;
                return sprintf(
                    $text,
                    str_pad(NumberFormat::format($portfolioValue ?? 0), 6),
                    str_pad(NumberFormat::format($balance ?? 0), 6),
                    str_pad(NumberFormat::format($honor), 6),
                    $user->getName() ?? $user->getFirstName(),
                );
            }, $leaderboard);
            $header = <<<TEXT
            <b>Leaderboard</b>
            [ <code>stocks</code> |  <code>bank</code>  |  <code>cash</code>  ]
            TEXT;
            array_unshift($text, $header);
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
