<?php

namespace App\Entity\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Season\Season;
use App\Entity\User\User;
use Money\Money;

class HonorFactory
{

    public static function create(
        Season $season,
        Chat $chat,
        ?User $sender,
        User $recipient,
        Money $amount,
    ): Honor {
        $honor = new Honor();
        $honor->setChat($chat);
        $honor->setSender($sender);
        $honor->setRecipient($recipient);
        $honor->setAmount($amount);
        $honor->setSeason($season);
        $honor->setCreatedAt(new \DateTime());
        $honor->setUpdatedAt(new \DateTime());
        return $honor;
    }

}
