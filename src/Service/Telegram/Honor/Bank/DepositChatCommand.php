<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Bank;

use App\Entity\Message\Message;
use App\Service\Honor\BankService;
use App\Service\Honor\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class DepositChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
        private readonly BankService $bankService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!deposit\s*(?<amount>\d+|max)(?<abbr>[A-Z]{1,2})?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            if ($matches['amount'] === 'max') {
                $amount = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
            } else {
                $amount = NumberFormat::getHonorValue($matches['amount'], $matches['abbr'] ?? null);
            }
            $transaction = $this->bankService->deposit($message->getChat(), $message->getUser(), $amount);
            $this->telegramService->replyTo($message, sprintf(
                'deposited %s Ehre',
                NumberFormat::money($transaction->getAmount()),
            ));
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
    }

    public function getSyntax(): string
    {
        return '!deposit [amount]';
    }

    public function getDescription(): string
    {
        return 'deposit Ehre into your bank account';
    }

}
