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

    public static function getBiasedRandomNumber(int $max, int $min = 1): bool
    {
        $bias = log($max);
        $random = rand(1, $max);
        $biasedRandom = floor(exp($random / $bias));
        return max($min, min($max, $biasedRandom));
    }

    public static function getNumber(int $max, int $min = 1): int
    {
        return mt_rand($min, $max);
    }

    public static function arrayElement(array $options): mixed
    {
        return $options[array_rand($options)];
    }

}