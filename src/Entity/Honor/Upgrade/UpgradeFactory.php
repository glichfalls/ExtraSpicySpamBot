<?php

namespace App\Entity\Honor\Upgrade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;

class UpgradeFactory
{

    public static function create(Chat $chat, User $user, UpgradeType $type): HonorUpgrade
    {
        $upgrade = new HonorUpgrade();
        $upgrade->setType($type);
        $upgrade->setUser($user);
        $upgrade->setChat($chat);
        $upgrade->setCreatedAt(new \DateTime());
        $upgrade->setUpdatedAt(new \DateTime());
        return $upgrade;
    }

}