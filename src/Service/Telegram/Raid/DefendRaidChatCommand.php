<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Raid\Raid;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\TelegramCallbackQueryListener;
use TelegramBot\Api\Types\Update;

class DefendRaidChatCommand extends AbstractRaidChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = 'raid:defend';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $raid = $this->raidRepository->getActiveRaid($chat);
            $this->validate($raid, $user);
            $raid->getDefenders()->add($user);
            $this->manager->persist($raid);
            $this->manager->flush();
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf('%s is now defending the raid', $user->getName()),
            );
        } catch (\RuntimeException $exception) {
            $this->logger->info(sprintf('failed to handle callback query [%s]', $exception->getMessage()));
        }
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(defend|d)$/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $raid = $this->getActiveRaid($message->getChat());
            $this->validate($raid, $message->getUser());
            $raid->getDefenders()->add($message->getUser());
            $this->manager->persist($raid);
            $this->manager->flush();
            $this->telegramService->replyTo($message, 'you are now defending the raid');
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
            return;
        }
    }

    private function validate(Raid $raid, User $supporter): void
    {
        if ($raid->getTarget()->getTelegramUserId() === $supporter->getTelegramUserId()) {
            throw new \RuntimeException('you cannot defend your own raid');
        }
        if ($raid->getLeader()->getTelegramUserId() === $supporter->getTelegramUserId()) {
            throw new \RuntimeException('the raid leader cannot defend the raid');
        }
        if ($raid->getDefenders()->filter(fn(User $user) => $user->getTelegramUserId() === $supporter->getTelegramUserId())->count() > 0) {
            throw new \RuntimeException('you already defend the raid');
        }
        if ($raid->getSupporters()->filter(fn(User $user) => $user->getTelegramUserId() === $supporter->getTelegramUserId())->count() > 0) {
            throw new \RuntimeException('you cannot support and defend the raid');
        }
    }

    public function getSyntax(): string
    {
        return '!defend or !d';
    }

    public function getDescription(): string
    {
        return 'defend the raid';
    }

}