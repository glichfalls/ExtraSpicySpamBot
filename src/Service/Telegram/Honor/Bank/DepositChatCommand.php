<?php

namespace App\Service\Telegram\Honor\Bank;

use App\Command\Migrations\CreateSet1UpgradeCommand;
use App\Entity\Chat\Chat;
use App\Entity\Honor\Bank\BankAccount;
use App\Entity\Honor\Bank\TransactionFactory;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\BankAccountRepository;
use App\Repository\HonorRepository;
use App\Repository\HonorUpgradeRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class DepositChatCommand extends AbstractTelegramChatCommand
{
    private const SERVICE_FEE = 0.05;
    private const MAX_DEPOSIT_PER_TRANSACTION = 1_000;
    private const MAX_BANK_ACCOUNT_BALANCE = 100_000;
    private const DEPOSIT_HOURS = 6;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private BankAccountRepository $bankAccountRepository,
        private HonorRepository $honorRepository,
        private HonorUpgradeRepository $honorUpgradeRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!deposit\s*(?<amount>\d+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $account = $this->bankAccountRepository->getByChatAndUser($message->getChat(), $message->getUser());
        if ($account === null) {
            $this->telegramService->replyTo($message, 'you do not have an account');
            return;
        }
        $latestTransaction = $account->getTransactions()->first();
        if ($latestTransaction && $latestTransaction->getCreatedAt()->getTimestamp() > (time() - (self::DEPOSIT_HOURS * 3600))) {
            $this->telegramService->replyTo($message, sprintf('you can only deposit every %d hours', self::DEPOSIT_HOURS));
            return;
        }
        $amount = (int) $matches['amount'];
        if ($this->canDepositAmount($message, $account, $amount)) {
            $serviceFee = (int)ceil($amount * self::SERVICE_FEE);
            $transactionAmount = $amount - $serviceFee;
            $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), -$amount));
            $account->addTransaction(TransactionFactory::create($transactionAmount));
            $this->manager->flush();
            $this->telegramService->replyTo($message, sprintf(
                'deposited %d honor (-%d%% service fee)',
                $transactionAmount,
                self::SERVICE_FEE * 100,
            ));
        }
    }

    private function canDepositAmount(Message $message, BankAccount $account, int $amount): bool
    {
        $honor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($honor < $amount) {
            $this->telegramService->replyTo($message, 'you do not have enough honor');
            return false;
        }
        if ($amount > self::MAX_DEPOSIT_PER_TRANSACTION) {
            $this->telegramService->replyTo($message, sprintf('you can only deposit up to %d honor at once', self::MAX_DEPOSIT_PER_TRANSACTION));
            return false;
        }
        $maxBalance = $this->getMaxBankAccountBalance($message->getChat(), $message->getUser());
        if ($account->getBalance() + $amount > $maxBalance) {
            $this->telegramService->replyTo($message, sprintf('you can only store up to %d honor in your account', $maxBalance));
            return false;
        }
        return true;
    }

    private function getMaxBankAccountBalance(Chat $chat, User $user): int
    {
        $upgrades = $this->honorUpgradeRepository->getUpgradesByChatAndUser($chat, $user);
        $codes = array_map(fn($upgrade) => $upgrade->getCode(), $upgrades);
        if (in_array(CreateSet1UpgradeCommand::BANK_UPGRADE_2, $codes)) {
            return CreateSet1UpgradeCommand::BANK_UPGRADE_2_MAX_BALANCE;
        }
        if (in_array(CreateSet1UpgradeCommand::BANK_UPGRADE_1, $codes)) {
            return CreateSet1UpgradeCommand::BANK_UPGRADE_1_MAX_BALANCE;
        }
        return self::MAX_BANK_ACCOUNT_BALANCE;
    }

    public function getHelp(): string
    {
        return '!deposit <amount>   deposit honor into your bank account';
    }

}