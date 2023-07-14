<?php

namespace App\Entity\OneToHowMuch;

use App\Entity\User\User;

class OneToHowMuchRoundFactory
{

    public static function create(User $challenger, User $opponent): OneToHowMuchRound
    {
        $round = new OneToHowMuchRound();
        $round->setChallenger($challenger);
        $round->setOpponent($opponent);
        $round->setCreatedAt(new \DateTime());
        $round->setUpdatedAt(new \DateTime());
        return $round;
    }

}