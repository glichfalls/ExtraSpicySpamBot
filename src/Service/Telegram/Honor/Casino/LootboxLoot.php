<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectCollection;
use App\Utils\Random;

enum LootboxLoot: string
{
    case SMALL = 'S';
    case MEDIUM = 'M';
    case LARGE = 'L';

    public function base(): float
    {
        return match ($this) {
            self::SMALL => 1,
            self::MEDIUM => 1.25,
            self::LARGE => 1.75,
        };
    }

    public function apply(?EffectCollection $effects): float
    {
        if ($effects === null) {
            return $this->base();
        }
        return $effects->apply($this->base(), min: $this->maxDebuff(), max: $this->maxBuff());
    }

    public function maxBuff(): float
    {
        return match ($this) {
            self::SMALL => 1.2,
            self::MEDIUM => 1.3,
            self::LARGE => 2,
        };
    }

    public function maxDebuff(): float
    {
        return match ($this) {
            self::SMALL => 0.1,
            self::MEDIUM => 0.2,
            self::LARGE => 0.5,
        };
    }

    public function minRarity(): ItemRarity
    {
        return match ($this) {
            default => ItemRarity::Common,
            self::MEDIUM => ItemRarity::Rare,
            self::LARGE => ItemRarity::Epic,
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
            self::SMALL => 1_000_000,
            self::MEDIUM => 1_000_000_000,
            self::LARGE => 1_000_000_000_000,
        };
    }

    public function minStockAmount(): int
    {
        return ceil($this->price() / 1000);
    }

    public function maxStockAmount(): int
    {
        return ceil($this->price() / 100);
    }

    public function stockAmount(): int
    {
        return Random::number($this->maxStockAmount(), $this->minStockAmount());
    }

    public function junkRate(?EffectCollection $effects): int
    {
        $result = 20 / $this->apply($effects);
        return (int) round($result);
    }

    public function itemRate(?EffectCollection $effects): int
    {
        $result = 5 * $this->apply($effects);
        return (int) round($result);
    }

    public function stockRate(?EffectCollection $effects): int
    {
        return 100 - $this->junkRate($effects) - $this->itemRate($effects);
    }
}