<?php

namespace App\Utils;

class Random
{

    public static function getPercentChance(int $probability): bool
    {
        if ($probability <= 0) {
            return false;
        }
        return self::getNumber(100) <= $probability;
    }

    public static function getNumber(int $max, int $min = 1): int
    {
        return mt_rand($min, $max);
    }

}