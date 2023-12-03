<?php

namespace App\Entity\Honor\HonorMillions\Draw;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;

class DrawFactory
{

    public static function create(Chat $chat, \DateTime $date, ?int $telegramThreadId = null): Draw
    {
        $draw = new Draw();
        $draw->setChat($chat);
        $draw->setDate($date);
        $draw->setTelegramThreadId($telegramThreadId);
        $draw->setGamblingLosses(Honor::currency(0));
        $draw->setPreviousJackpot(Honor::currency(0));
        return $draw;
    }

}