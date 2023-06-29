<?php

namespace App\Utils;

class RateLimitUtils
{

    public static function getDaysFrom(\DateTime $date): int
    {
        return floor(self::getHoursFrom($date) / 24);
    }

    public static function getHoursFrom(\DateTime $date): int
    {
        return floor(self::getMinutesFrom($date) / 60);
    }

    public static function getMinutesFrom(\DateTime $date): int
    {
        return floor(self::getSecondsFrom($date) / 60);
    }

    public static function getSecondsFrom(\DateTime $date): int
    {
        return abs(time() - $date->getTimestamp());
    }

}