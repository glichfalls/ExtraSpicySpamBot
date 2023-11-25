<?php

namespace App\Entity\Honor;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use Money\Money;

class HonorFactory
{

    public static function create(
        Chat $chat,
        ?User $sender,
        User $recipient,
        Money $amount
    ): Honor {
        $honor = new Honor();
        $honor->setChat($chat);
        $honor->setSender($sender);
        $honor->setRecipient($recipient);
        $honor->setAmount($amount);
        $honor->setCreatedAt(new \DateTime());
        $honor->setUpdatedAt(new \DateTime());
        return $honor;
    }

    public static function createPositive(Chat $chat, User $recipient, Money $amount): Honor
    {
        return self::create($chat, null, $recipient, $amount->absolute());
    }

    public static function createNegative(Chat $chat, User $recipient, Money $amount): Honor
    {
        return self::create($chat, null, $recipient, $amount->absolute()->negative());
    }

}
