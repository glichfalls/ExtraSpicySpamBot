<?php

namespace App\Service\Telegram;

use App\Entity\Message\Message;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TelegramBot\Api\Types\Update;

#[AutoconfigureTag('telegram.chat_command')]
interface TelegramChatCommand
{

    /**
     * Check if the message should be handled by this command
     * Matches from a regex can be extracted and passed to the handle method
     */
    public function matches(Update $update, Message $message, array &$matches): bool;

    /**
     * handle the message with the extracted matches
     */
    public function handle(Update $update, Message $message, array $matches): void;

}