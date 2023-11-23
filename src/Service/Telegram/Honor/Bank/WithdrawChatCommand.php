<?php

namespace App\Service\Telegram\Honor\Bank;

use App\Entity\Honor\Bank\TransactionFactory;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Repository\BankAccountRepository;
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
        private readonly BankAccountRepository $bankAccountRepository,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!withdraw\s*(?<amount>\d+|max)(?<abbr>[kmbtq])?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $account = $this->bankAccountRepository->getByChatAndUser($message->getChat(), $message->getUser());
        if ($account === null) {
            $this->telegramService->replyTo($message, 'you do not have an account');
            return;
        }
        $balance = $account->getBalance();
        if ($matches['amount'] === 'max') {
            $amount = $balance;
        } else {
            $amount = NumberFormat::getIntValue($matches['amount'], $matches['abbr'] ?? null);
        }
        if ($balance < $amount) {
            $this->telegramService->replyTo($message, sprintf('there is not enough ehre in your bank account (balance: %s ehre)', NumberFormat::format($balance)));
            return;
        }
        $account->addTransaction(TransactionFactory::create(-$amount));
        $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), $amount));
        $this->manager->flush();
        $this->telegramService->replyTo($message, sprintf(
            'withdrew %s Ehre.',
            NumberFormat::format($amount),
        ));
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
