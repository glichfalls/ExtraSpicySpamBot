<?php

namespace App\Entity\Chat;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class ChatFactory
{

    public static function createFromUpdate(Update $update): Chat
    {
        $telegramChat = $update->getMessage()->getChat();
        return self::extracted($telegramChat);
    }

    public static function createFromMessage(Message $message): Chat
    {
        $telegramChat = $message->getChat();
        return self::extracted($telegramChat);
    }

    private static function extracted(\TelegramBot\Api\Types\Chat $telegramChat): Chat
    {
        $chat = new Chat();
        $chat->setConfig(new ChatConfig());
        $chat->setChatId($telegramChat->getId());
        if ($telegramChat->getType() === 'private') {
            $chat->setName($telegramChat->getUsername());
        } else {
            $chat->setName($telegramChat->getTitle());
        }
        $chat->setCreatedAt(new \DateTime());
        $chat->setUpdatedAt(new \DateTime());
        return $chat;
    }

}