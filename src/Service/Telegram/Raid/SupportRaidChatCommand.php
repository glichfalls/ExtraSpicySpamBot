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

class SupportRaidChatCommand extends AbstractTelegramChatCommand
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
        return preg_match('/^!(support|s)$/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.noActiveRaid'));
            return;
        }
        if ($raid->getTarget()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.cannotSupportOwnRaid'));
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.raidLeaderAutomaticallySupportsRaid'));
            return;
        }
        if ($raid->getSupporters()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.alreadySupportRaid'));
            return;
        }
        if ($raid->getDefenders()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.cannotSupportAndDefend'));
            return;
        }
        $raid->getSupporters()->add($message->getUser());
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.nowSupportingRaid'));
    }

    public function getHelp(): string
    {
        return '!support | !s   support the active raid';
    }

}