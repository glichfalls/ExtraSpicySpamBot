<?php

namespace App\Utils;

class RateLimitUtils
{

    public static function getHoursSinceNow(\DateTime $date): int
    {
        return floor(self::getMinutesSinceNow($date) / 60);
    }

    public static function getMinutesSinceNow(\DateTime $date): int
    {
        return floor(self::getSecondsSinceNow($date) / 60);
    }

    public static function getSecondsSinceNow(\DateTime $date): int
    {
        return abs(time() - $date->getTimestamp());
    }

}