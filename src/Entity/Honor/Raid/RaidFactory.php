<?php

namespace App\Entity\Honor\Raid;

use App\Entity\Chat\Chat;
use App\Entity\User\User;

class RaidFactory
{

    public static function create(Chat $chat, User $leader, User $target, int $amount): Raid
    {
        $raid = new Raid();
        $raid->setChat($chat);
        $raid->setLeader($leader);
        $raid->setTarget($target);
        $raid->setAmount($amount);
        $raid->setIsActive(true);
        $raid->setCreatedAt(new \DateTime());
        $raid->setUpdatedAt(new \DateTime());
        return $raid;
    }

}