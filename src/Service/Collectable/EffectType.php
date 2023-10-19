<?php

namespace App\Service\Collectable;

enum EffectType
{
    case LUCK;
    case GAMBLE_LUCK;
    case LOOTBOX_LUCK;
    case PASSIVE_HONOR;
    case RAID_GUARD;
    case OFFENSIVE_RAID_SUCCESS;
    case OFFENSIVE_RAID_LOOT;
    case DEFENSIVE_RAID_SUCCESS;
    case DEFENSIVE_RAID_LOOT;

    public static function all(): array
    {
        return [
            self::LUCK,
            self::GAMBLE_LUCK,
            self::LOOTBOX_LUCK,
            self::PASSIVE_HONOR,
            self::RAID_GUARD,
            self::OFFENSIVE_RAID_SUCCESS,
            self::DEFENSIVE_RAID_SUCCESS,
        ];
    }

    public static function keyValue(): array
    {
        return [
            self::LUCK->key() => self::LUCK->label(),
            self::GAMBLE_LUCK->key() => self::GAMBLE_LUCK->label(),
            self::LOOTBOX_LUCK->key() => self::LOOTBOX_LUCK->label(),
            self::PASSIVE_HONOR->key() => self::PASSIVE_HONOR->label(),
            self::RAID_GUARD->key() => self::RAID_GUARD->label(),
            self::OFFENSIVE_RAID_SUCCESS->key() => self::OFFENSIVE_RAID_SUCCESS->label(),
            self::DEFENSIVE_RAID_SUCCESS->key() => self::DEFENSIVE_RAID_SUCCESS->label(),
        ];
    }

    public function key(): string
    {
        return match ($this) {
            self::LUCK => 'LUCK',
            self::GAMBLE_LUCK => 'GAMBLE_LUCK',
            self::LOOTBOX_LUCK => 'LOOTBOX_LUCK',
            self::PASSIVE_HONOR => 'PASSIVE_HONOR',
            self::RAID_GUARD => 'RAID_GUARD',
            self::OFFENSIVE_RAID_SUCCESS => 'OFFENSIVE_RAID_SUCCESS',
            self::DEFENSIVE_RAID_SUCCESS => 'DEFENSIVE_RAID_SUCCESS',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::LUCK => 'Luck',
            self::GAMBLE_LUCK => 'Gamble Luck',
            self::LOOTBOX_LUCK => 'Lootbox Luck',
            self::PASSIVE_HONOR => 'Passive Ehre',
            self::RAID_GUARD => 'Raid Guard',
            self::OFFENSIVE_RAID_SUCCESS => 'Offensive Raid Success',
            self::DEFENSIVE_RAID_SUCCESS => 'Defensive Raid Success',
        };
    }
}