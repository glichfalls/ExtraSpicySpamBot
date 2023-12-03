<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor;

use App\Dto\NetWorth;
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
        $data = $this->honorService->getHonorLeaderboardByChat($chat);
        if (count($data) === 0) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.noLeaderboard'));
        } else {
            $leaderboard = [];
            foreach ($data as $entry) {
                $user = $this->userService->getById((string) $entry['id']);
                $balance = $this->bankService->getAccount($chat, $user)->getBalance();
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
                $leaderboard[] = new NetWorth($user, Honor::currency($entry['amount']), $balance, $portfolioValue);
            }
            // sort by total
            usort($leaderboard, fn (NetWorth $a, NetWorth $b) => $b->getTotal()->compare($a->getTotal()));
            // format text
            $text = array_map(function (NetWorth $netWorth) use ($chat) {
                $text = <<<TEXT
                [ <code>%s</code> | <code>%s</code> | <code>%s</code> ] <b>%s</b>
                TEXT;
                return sprintf(
                    $text,
                    NumberFormat::money($netWorth->portfolio),
                    NumberFormat::money($netWorth->balance),
                    NumberFormat::money($netWorth->cash),
                    $netWorth->user->getName() ?? $netWorth->user->getFirstName(),
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
