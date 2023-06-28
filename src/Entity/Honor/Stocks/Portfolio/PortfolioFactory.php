<?php

namespace App\Entity\Honor\Stocks\Portfolio;

use App\Entity\Chat\Chat;
use App\Entity\User\User;

class PortfolioFactory
{

    public static function create(Chat $chat, User $user): Portfolio
    {
        $portfolio = new Portfolio();
        $portfolio->setChat($chat);
        $portfolio->setUser($user);
        return $portfolio;
    }

}