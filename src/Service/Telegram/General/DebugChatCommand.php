<?php

namespace App\Service\Telegram\General;

use App\Entity\Message\Message;
use App\Service\Telegram\AbstractTelegramChatCommand;
use TelegramBot\Api\Types\Update;

class DebugChatCommand extends AbstractTelegramChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!debug (on|off)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $config = $message->getChat()->getConfig();
        $config->setDebugEnabled(!$config->isDebugEnabled());
        $this->telegramService->replyTo(
            $message,
            sprintf('Debug mode is now %s', $config->isDebugEnabled() ? 'on' : 'off'),
        );
    }

}