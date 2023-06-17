<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Message\Message;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\TelegramBaseService;
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
        TelegramBaseService $telegramService,
        private HonorService $honorService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(leaderboard)/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $leaderboard = $this->honorService->getLeaderboardByChat($message->getChat());
        if ($leaderboard === null) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.noLeaderboard'));
        } else {
            $this->telegramService->replyTo($message, $leaderboard);
        }
    }

}