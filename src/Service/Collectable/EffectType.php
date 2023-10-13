<?php

namespace App\Service\Collectable;

enum EffectType
{
    case LUCK;
    case GAMBLE_LUCK;
    case LOOTBOX_LUCK;

    public static function all(): array
    {
        return [
            self::LUCK,
            self::GAMBLE_LUCK,
            self::LOOTBOX_LUCK,
        ];
    }

    public static function keyValue(): array
    {
        return [
            self::LUCK->key() => self::LUCK->label(),
            self::GAMBLE_LUCK->key() => self::GAMBLE_LUCK->label(),
            self::LOOTBOX_LUCK->key() => self::LOOTBOX_LUCK->label(),
        ];
    }

    public function key(): string
    {
        return match ($this) {
            self::LUCK => 'LUCK',
            self::GAMBLE_LUCK => 'GAMBLE_LUCK',
            self::LOOTBOX_LUCK => 'LOOTBOX_LUCK',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::LUCK => 'Luck',
            self::GAMBLE_LUCK => 'Gamble Luck',
            self::LOOTBOX_LUCK => 'Lootbox Luck',
        };
    }
}