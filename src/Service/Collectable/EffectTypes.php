<?php

namespace App\Service\Collectable;

final class EffectTypes
{
    public const LUCK = 'LUCK';
    public const GAMBLE_LUCK = 'GAMBLE_LUCK';
    public const LOOTBOX_LUCK = 'LOOTBOX_LUCK';

    public const ALL = [
        self::LUCK,
        self::GAMBLE_LUCK,
        self::LOOTBOX_LUCK,
    ];
}