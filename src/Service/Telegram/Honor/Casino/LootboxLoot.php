<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectCollection;

enum LootboxLoot: string
{
    case SMALL = 'S';
    case MEDIUM = 'M';
    case LARGE = 'L';
    case XL = 'XL';
    case XXL = 'XXL';

    public function getMultiplier(?EffectCollection $effects = null): float
    {
        $multiplier = $effects?->apply($this->base()) ?? $this->base();
        if ($multiplier > $this->maxBuff()) {
            $multiplier = $this->maxBuff();
        }
        if ($multiplier < $this->maxDebuff()) {
            $multiplier = $this->maxDebuff();
        }
        return $multiplier;
    }

    public function base(): float
    {
        return match ($this) {
            self::SMALL => 1,
            self::MEDIUM => 1.2,
            self::LARGE => 1.3,
            self::XL => 1.5,
            self::XXL => 1.8,
        };
    }

    public function maxBuff(): float
    {
        return match ($this) {
            self::SMALL => 1.2,
            self::MEDIUM => 1.3,
            self::LARGE => 1.5,
            self::XL => 1.8,
            self::XXL => 2.5,
        };
    }

    public function maxDebuff(): float
    {
        return match ($this) {
            self::SMALL => 0.1,
            self::MEDIUM => 0.2,
            self::LARGE => 0.3,
            self::XL => 0.5,
            self::XXL => 0.7,
        };
    }

    public function minRarity(): ItemRarity
    {
        return match ($this) {
            default => ItemRarity::Common,
            self::XL => ItemRarity::Rare,
            self::XXL => ItemRarity::Epic,
        };
    }

    public function maxRarity(): ItemRarity
    {
        return match ($this) {
            default => ItemRarity::Legendary,
            self::SMALL => ItemRarity::Rare,
            self::MEDIUM => ItemRarity::Epic,
        };
    }

    public function price(): int
    {
        return match ($this) {
            self::SMALL => 10_000,
            self::MEDIUM => 1_000_000,
            self::LARGE => 1_000_000_000,
            self::XL => 100_000_000_000,
            self::XXL => 10_000_000_000_000,
        };
    }

    public function junkRate(?EffectCollection $effects): int
    {
        return (int) floor(15 / $this->getMultiplier($effects));
    }

    public function badLootRate(?EffectCollection $effects): int
    {
        return (int) floor(60 / $this->getMultiplier($effects));
    }

    public function honorLootRate(?EffectCollection $effects): int
    {
        return (int) ceil(30 * $this->getMultiplier($effects));
    }

    public function itemLootRate(?EffectCollection $effects): int
    {
        return (int) ceil(5 * $this->getMultiplier($effects));
    }
}