<?php

namespace App\Service\Telegram\Honor\Bank;

use App\Entity\Honor\Bank\BankAccount;
use App\Entity\Honor\Bank\TransactionFactory;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Repository\BankAccountRepository;
use App\Repository\HonorRepository;
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
        private readonly BankAccountRepository $bankAccountRepository,
        private readonly HonorRepository $honorRepository,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!deposit\s*(?<amount>\d+|max)(?<abbr>[kmbtqi]{1,2})?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $account = $this->bankAccountRepository->getByChatAndUser($message->getChat(), $message->getUser());
        if ($account === null) {
            $account = new BankAccount();
            $account->setChat($message->getChat());
            $account->setUser($message->getUser());
            $this->manager->persist($account);
        }
        if ($matches['amount'] === 'max') {
            $amount = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        } else {
            $amount = NumberFormat::getIntValue($matches['amount'], $matches['abbr'] ?? null);
        }
        if ($this->canDepositAmount($message, $account, $amount)) {
            $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), -$amount));
            $account->addTransaction(TransactionFactory::create($amount));
            $this->manager->flush();
            $this->telegramService->replyTo($message, sprintf(
                'deposited %s Ehre',
                NumberFormat::format($amount),
            ));
        }
    }

    private function canDepositAmount(Message $message, BankAccount $account, int $amount): bool
    {
        $honor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($honor < $amount) {
            $this->telegramService->replyTo($message, 'you do not have enough Ehre');
            return false;
        }
        if ($account->getBalance() > PHP_INT_MAX - $amount) {
            $this->telegramService->replyTo($message, 'your bank account is full');
            return false;
        }
        return true;
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
