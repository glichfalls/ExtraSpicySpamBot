<?php

namespace App\Entity\Stocks\Portfolio;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Season\Season;
use App\Entity\User\User;

class PortfolioFactory
{

    public static function create(Season $season, Chat $chat, User $user): Portfolio
    {
        $portfolio = new Portfolio();
        $portfolio->setSeason($season);
        $portfolio->setChat($chat);
        $portfolio->setUser($user);
        return $portfolio;
    }

}