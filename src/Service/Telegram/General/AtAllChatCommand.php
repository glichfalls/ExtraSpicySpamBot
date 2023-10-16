<?php

namespace App\Service\Telegram\General;

use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\AbstractTelegramChatCommand;
use TelegramBot\Api\Types\Update;

class AtAllChatCommand extends AbstractTelegramChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/@all/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $userList = $message->getChat()->getUsers()->map(fn(User $user) => sprintf('@%s', $user->getName() ?? $user->getFirstName()));
        $this->telegramService->replyTo(
            $message,
            sprintf('%s %s',
                $message->getMessage(),
                implode(' ', $userList->getValues()),
            ),
        );
    }

}