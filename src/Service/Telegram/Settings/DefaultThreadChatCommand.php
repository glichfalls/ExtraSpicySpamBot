<?php

namespace App\Service\Telegram\Settings;

use App\Entity\Message\Message;
use App\Service\Telegram\AbstractTelegramChatCommand;
use TelegramBot\Api\Types\Update;

class DefaultThreadChatCommand extends AbstractTelegramChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return $message->getMessage() === '!default';
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $message->getChat()->getConfig()->setDefaultThreadId($message->getTelegramThreadId());
        $this->manager->flush();
        $this->telegramService->sendText(
            $message->getTelegramThreadId(),
            sprintf('Default thread set to %s', $message->getTelegramThreadId()),
            threadId: $message->getTelegramThreadId(),
        );
    }

}