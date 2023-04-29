<?php

namespace App\Entity\Honor;

use App\Entity\Chat\Chat;
use App\Entity\User\User;

class HonorFactory
{

    public static function create(
        Chat $chat,
        User $sender,
        User $recipient,
        int $amount
    ): Honor
    {
        $honor = new Honor();
        $honor->setChat($chat);
        $honor->setSender($sender);
        $honor->setRecipient($recipient);
        $honor->setAmount($amount);
        $honor->setCreatedAt(new \DateTime());
        $honor->setUpdatedAt(new \DateTime());
        $recipient->getHonor()->add($honor);
        return $honor;
    }

}