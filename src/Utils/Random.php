<?php

namespace App\Utils;

use App\Strategy\Effect\EffectStrategyFactory;

class Random
{

    public static function getPercentChance(int $probability): bool
    {
        return self::getNumber(100) <= $probability;
    }

    public static function getNumber(int $max, int $min = 1): int
    {
        return mt_rand($min, $max);
    }

}