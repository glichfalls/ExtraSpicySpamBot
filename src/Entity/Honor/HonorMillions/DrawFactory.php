<?php

namespace App\Entity\Honor\HonorMillions;

class DrawFactory
{

    public static function create(\DateTime $date): Draw
    {
        $draw = new Draw();
        $draw->setDate($date);
        return $draw;
    }

}