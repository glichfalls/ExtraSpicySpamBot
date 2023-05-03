<?php

namespace App\Entity\Subscription;

final class SubscriptionTypes
{
    public const TYPE_WASTE_DISPOSAL = 'waste_disposal';

    public const ALLOWED_TYPES = [
        self::TYPE_WASTE_DISPOSAL,
    ];

    public static function isAllowed(?string $type): bool
    {
        return $type === null || in_array($type, self::ALLOWED_TYPES, true);
    }
}