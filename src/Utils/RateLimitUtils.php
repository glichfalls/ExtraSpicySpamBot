<?php

namespace App\Utils;

class RateLimitUtils
{

    public static function getDaysFrom(\DateTimeInterface $date): int
    {
        return (int) floor(self::getHoursFrom($date) / 24);
    }

    public static function getHoursFrom(\DateTimeInterface $date): int
    {
        return (int) floor(self::getMinutesFrom($date) / 60);
    }

    public static function getMinutesFrom(\DateTimeInterface $date): int
    {
        return (int) floor(self::getSecondsFrom($date) / 60);
    }

    public static function getSecondsFrom(\DateTimeInterface $date): int
    {
        return (int) abs(time() - $date->getTimestamp());
    }

}