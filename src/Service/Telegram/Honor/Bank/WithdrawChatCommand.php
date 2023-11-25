<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Bank;

use App\Entity\Message\Message;
use App\Service\Bank\BankService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class WithdrawChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly BankService $bankService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!withdraw\s*(?<amount>\d+|max)(?<abbr>[kmbtqi]{1,2})?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $account = $this->bankService->getBankAccount($message->getChat(), $message->getUser());
            if ($matches['amount'] === 'max') {
                $amount = $account->getBalance();
            } else {
                $amount = NumberFormat::getIntValue($matches['amount'], $matches['abbr'] ?? null);
            }
            $this->bankService->withdraw($message->getChat(), $message->getUser(), $amount);
            $this->telegramService->replyTo($message, sprintf(
                'withdrew %s Ehre.',
                NumberFormat::format($amount),
            ));
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
    }

    public function getSyntax(): string
    {
        return '!withdraw [amount] or max';
    }

    public function getDescription(): string
    {
        return 'withdraw Ehre from your bank account';
    }

}
