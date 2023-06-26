<?php

namespace App\Service\Telegram\Honor\Bank;

use App\Entity\Honor\Bank\Transaction;
use App\Entity\Honor\Bank\TransactionFactory;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Repository\BankAccountRepository;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
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
        private BankAccountRepository $bankAccountRepository,
        private HonorRepository $honorRepository,
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
        $latestTransaction = $account->getTransactions()->last();
        if ($latestTransaction !== null && $latestTransaction->getCreatedAt()->getTimestamp() > (time() - 60 * 60 * 12)) {
            $this->telegramService->replyTo($message, 'you can only deposit every 12 hours');
            return;
        }
        $amount = (int) $matches['amount'];
        $honor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($honor < $amount) {
            $this->telegramService->replyTo($message, 'you do not have enough honor');
            return;
        }
        $serviceFee = (int) ceil($amount * 0.05);
        $transactionAmount = $amount - $serviceFee;
        $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), -$amount));
        $account->addTransaction(TransactionFactory::create($transactionAmount));
        $this->manager->flush();
        $this->telegramService->replyTo($message, sprintf(
            'deposited %d honor (-5%% (%d) service fee)',
            $transactionAmount,
            $serviceFee,
        ));
    }

}