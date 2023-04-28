<?php

namespace App\Entity\Chat;

use TelegramBot\Api\Types\Update;

class ChatFactory
{

    public static function createFromUpdate(Update $update): Chat
    {
        $telegramChat = $update->getMessage()->getChat();
        $chat = new Chat();
        $chat->setChatId($telegramChat->getId());
        if ($telegramChat->getType() === 'private') {
            $chat->setName($telegramChat->getUsername());
        } else {
            $chat->setName($telegramChat->getTitle());
        }
        return $chat;
    }

}