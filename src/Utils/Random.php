<?php

namespace App\Utils;

use App\Entity\User\User;
use App\Strategy\Effect\EffectStrategyFactory;
use App\Strategy\Effect\Types;

class Random
{

    public static function getPercentChance(int $probability, ?User $user): bool
    {
        if ($user !== null) {
            $effects = $user->getCollectablesByEffectType(Types::LUCK);
            foreach ($effects as $effect) {

            }
        }
        return self::getNumber(100) <= $probability;
    }

    public static function getNumber(int $max, int $min = 1): int
    {
        return mt_rand($min, $max);
    }

}