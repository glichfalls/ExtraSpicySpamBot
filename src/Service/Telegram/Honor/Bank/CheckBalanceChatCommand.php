<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Bank;

use App\Entity\Message\Message;
use App\Repository\BankAccountRepository;
use App\Service\Honor\BankService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

final class CheckBalanceChatCommand extends AbstractTelegramChatCommand
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
        return preg_match('/^!balance$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $account = $this->bankService->getAccount($message->getChat(), $message->getUser());
        $this->telegramService->replyTo(
            $message,
            sprintf('your bank balance is %s ehre', NumberFormat::money($account->getBalance()))
        );
    }

    public function getSyntax(): string
    {
        return '!withdraw [amount] or max';
    }

    public function getDescription(): string
    {
        return 'check your ehre bank balance';
    }

}
