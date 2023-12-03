<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Service\Honor\BankService;
use App\Service\Honor\HonorService;
use App\Service\Stocks\StockService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Service\UserService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class ShowLeaderboardChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
        private readonly UserService $userService,
        private readonly BankService $bankService,
        private readonly StockService $stockService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(leaderboard)/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $chat = $message->getChat();
        $leaderboard = $this->honorService->getHonorLeaderboardByChat($chat);
        if (count($leaderboard) === 0) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.noLeaderboard'));
        } else {
            foreach ($leaderboard as $key => $entry) {
                $cash = Honor::currency($entry['amount']);
                $leaderboard[$key]['amount'] = $cash;
                $user = $this->userService->getById((string) $entry['id']);
                $leaderboard[$key]['user'] = $user;
                $balance = $this->bankService->getBankAccount($chat, $user)?->getBalance();
                $leaderboard[$key]['balance'] = $balance;
                $portfolio = $this->stockService->getPortfolioByChatAndUser($chat, $user);
                try {
                    $portfolioValue = $this->stockService->getPortfolioBalance($portfolio);
                } catch (\Exception $exception) {
                    $portfolioValue = Honor::currency(0);
                    $this->logger->error('failed to get portfolio balance', [
                        'exception' => $exception,
                        'chat' => $chat->getId(),
                        'user' => $user->getId(),
                    ]);
                }
                $leaderboard[$key]['portfolio'] = $portfolioValue;
                $leaderboard[$key]['total'] = $cash->add($balance)->add($portfolioValue);
            }
            // sort by total
            usort($leaderboard, fn ($a, $b) => $a['total']->compare($b['total']));
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
                    NumberFormat::money($portfolioValue ?? 0),
                    NumberFormat::money($balance ?? 0),
                    NumberFormat::money($honor),
                    $user->getName() ?? $user->getFirstName(),
                );
            }, $leaderboard);
            $header = <<<TEXT
            <b>Leaderboard</b>
            [ stocks | bank | cash ]
            TEXT;
            array_unshift($text, $header);
            $text = implode(PHP_EOL, $text);
            $this->telegramService->replyTo($message, $text, parseMode: 'HTML');
        }
    }

    public function getHelp(): string
    {
        return '!leaderboard   shows the leaderboard';
    }

}
