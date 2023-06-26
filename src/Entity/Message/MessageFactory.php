<?php

namespace App\Entity\Message;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use \TelegramBot\Api\Types\Message as TelegramMessage;

class MessageFactory
{

    public static function create(Chat $chat, User $sender, TelegramMessage $telegramMessage): Message
    {
        $message = new Message();
        $message->setChat($chat);
        $message->setUser($sender);
        $message->setTelegramMessageId($telegramMessage->getMessageId());
        $message->setTelegramThreadId($telegramMessage->getMessageThreadId());
        $message->setMessage($telegramMessage->getText());
        $message->setCreatedAt(new \DateTime());
        $message->setUpdatedAt(new \DateTime());
        return $message;
    }

}