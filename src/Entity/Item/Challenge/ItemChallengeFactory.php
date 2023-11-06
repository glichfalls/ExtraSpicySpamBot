<?php

namespace App\Entity\Item\Challenge;

use App\Entity\Item\ItemInstance;

class ItemChallengeFactory
{

    public static function create(ItemInstance $instance): ItemChallenge
    {
        $challenge = new ItemChallenge();
        $challenge->setInstance($instance);
        $challenge->setCreatedAt(new \DateTime());
        $challenge->setUpdatedAt(new \DateTime());
        return $challenge;
    }

}