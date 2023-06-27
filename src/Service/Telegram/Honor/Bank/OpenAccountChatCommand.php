<?php

namespace App\Service\Telegram\Honor\Bank;

use App\Entity\Honor\Bank\BankAccount;
use App\Entity\Message\Message;
use App\Repository\BankAccountRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class OpenAccountChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private BankAccountRepository $bankAccountRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!create bank account$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $account = $this->bankAccountRepository->getByChatAndUser($message->getChat(), $message->getUser());
        if ($account !== null) {
            $this->telegramService->replyTo($message, 'you already have an account');
            return;
        }
        $account = new BankAccount();
        $account->setChat($message->getChat());
        $account->setUser($message->getUser());
        $this->manager->persist($account);
        $this->manager->flush();
        $this->telegramService->replyTo($message, 'bank account created');
    }

    public function getHelp(): string
    {
        return '!create bank account   create a bank account';
    }

}