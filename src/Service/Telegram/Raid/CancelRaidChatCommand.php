<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Message\Message;
use App\Repository\RaidRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class CancelRaidChatCommand extends AbstractTelegramChatCommand
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
        return preg_match('/^!cancel raid/', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.noActiveRaid'));
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() !== $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.notLeaderError'));
            return;
        }
        $raid->setIsActive(false);
        $this->manager->flush();
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            $this->translator->trans('telegram.raid.raidCanceled'),
            threadId: $message->getTelegramThreadId(),
        );
    }

    public function getHelp(): string
    {
        return '!cancel raid   cancels the current raid';
    }

}