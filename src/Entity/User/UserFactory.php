<?php

namespace App\Entity\User;

use \TelegramBot\Api\Types\User as TelegramUser;
use TelegramBot\Api\Types\Update;

class UserFactory
{

    public static function createFromUpdate(Update $update): User
    {
        return self::createFromTelegramUser($update->getMessage()->getFrom());
    }

    public static function createFromTelegramUser(TelegramUser $telegramUser): User
    {
        $user = new User();
        $user->setTelegramUserId($telegramUser->getId());
        $user->setName($telegramUser->getUsername());
        $user->setFirstName($telegramUser->getFirstName());
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        return $user;
    }

}