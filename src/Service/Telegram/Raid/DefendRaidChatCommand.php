<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\RaidRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class DefendRaidChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface    $translator,
        LoggerInterface        $logger,
        TelegramService        $telegramService,
        private RaidRepository $raidRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(defend|d)$/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, 'no active raid');
            return;
        }
        if ($raid->getTarget()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'you cannot defend your own raid');
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'the raid leader cannot defend the raid');
            return;
        }
        if ($raid->getDefenders()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, 'you already defend the raid');
            return;
        }
        if ($raid->getSupporters()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, 'you cannot support and defend the raid');
            return;
        }
        $raid->getDefenders()->add($message->getUser());
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->replyTo($message, 'you are now defending the raid');
    }

}