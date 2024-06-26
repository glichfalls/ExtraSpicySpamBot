<?php

namespace App\Utils;

class Random
{

    public static function getPercentChance(int $probability): bool
    {
        if ($probability <= 0) {
            return false;
        }
        return self::number(100) <= $probability;
    }

    public static function number(int $max, int $min = 1): int
    {
        return mt_rand($min, $max);
    }

    public static function arrayElement(array $options): mixed
    {
        return $options[array_rand($options)];
    }

}