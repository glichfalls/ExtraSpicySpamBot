<?php

namespace App\Entity\Honor\HonorMillions\Draw;

use App\Entity\Chat\Chat;

class DrawFactory
{

    public static function create(Chat $chat, \DateTime $date): Draw
    {
        $draw = new Draw();
        $draw->setChat($chat);
        $draw->setDate($date);
        return $draw;
    }

}