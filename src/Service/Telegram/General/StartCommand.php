<?php

namespace App\Service\Telegram\General;

use App\Entity\Message\Message;
use App\Service\Telegram\AbstractTelegramChatCommand;
use TelegramBot\Api\Types\Update;

class StartCommand extends AbstractTelegramChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^\/start$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $this->telegramService->replyTo($message,
            'Hello! Im still under development, but you can already use me! type !help to see what I can do!',
        );
    }

}