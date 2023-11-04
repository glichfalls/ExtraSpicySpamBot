<?php

namespace App\Entity\Item\Effect;

enum EffectType: string
{
    case LUCK = 'Luck';
    case GAMBLE_LUCK = 'Gamble Luck';
    case LOOTBOX_LUCK = 'Lootbox Luck';
    case PASSIVE_HONOR = 'Passive Ehre';
    case RAID_GUARD = 'Raid Guard';
    case OFFENSIVE_RAID_SUCCESS = 'Offensive Raid Success';
    case OFFENSIVE_RAID_LOOT = 'Offensive Raid Loot';
    case DEFENSIVE_RAID_SUCCESS = 'Defensive Raid Success';
    case DEFENSIVE_RAID_LOOT = 'Defensive Raid Loot';

    /**
     * @return array<string, string>
     */
    public static function keyValue(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->name, self::cases()),
            array_map(fn ($case) => $case->value, self::cases())
        );
    }
}