<?php

namespace App\Entity\User;

use TelegramBot\Api\Types\Update;

class UserFactory
{

    public static function createFromUpdate(Update $update): User
    {
        $user = new User();
        $user->setTelegramUserId($update->getMessage()->getFrom()->getId());
        $user->setName($update->getMessage()->getFrom()->getUsername());
        $user->setFirstName($update->getMessage()->getFrom()->getFirstName());
        $user->setHonor(0);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        return $user;
    }

}