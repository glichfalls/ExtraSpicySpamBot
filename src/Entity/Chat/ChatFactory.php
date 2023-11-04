<?php

namespace App\Entity\Chat;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class ChatFactory
{

    public static function create(string $id, string $name): Chat
    {
        $chat = new Chat();
        $chat->setConfig(new ChatConfig());
        $chat->setChatId($id);
        $chat->setName($name);
        $chat->setCreatedAt(new \DateTime());
        $chat->setUpdatedAt(new \DateTime());
        return $chat;
    }

    public static function createFromUpdate(Update $update): Chat
    {
        $telegramChat = $update->getMessage()->getChat();
        return self::createFromTelegramChat($telegramChat);
    }

    public static function createFromMessage(Message $message): Chat
    {
        $telegramChat = $message->getChat();
        return self::createFromTelegramChat($telegramChat);
    }

    private static function createFromTelegramChat(\TelegramBot\Api\Types\Chat $telegramChat): Chat
    {
        if ($telegramChat->getType() === 'private') {
            return self::create(
                $telegramChat->getId(),
                sprintf('Private Chat with %s', $telegramChat->getUsername() ?? $telegramChat->getFirstName())
            );
        } else {
            return self::create($telegramChat->getId(), $telegramChat->getTitle());
        }
    }

}
