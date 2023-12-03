<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Honor\Honor;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectCollection;
use App\Utils\Random;
use Money\Money;

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
            self::SMALL => 1.25,
            self::MEDIUM => 1.5,
            self::LARGE => 2,
        };
    }

    public function maxDebuff(): float
    {
        return match ($this) {
            self::SMALL => 0.1,
            self::MEDIUM => 0.2,
            self::LARGE => 0.4,
        };
    }

    public function minRarity(): ItemRarity
    {
        return match ($this) {
            default => ItemRarity::Common,
            self::LARGE => ItemRarity::Rare,
        };
    }

    public function maxRarity(): ItemRarity
    {
        return match ($this) {
            self::SMALL => ItemRarity::Rare,
            self::MEDIUM => ItemRarity::Epic,
            self::LARGE => ItemRarity::Legendary,
        };
    }

    public function price(): Money
    {
        return match ($this) {
            self::SMALL => Honor::currency(1_000_000),
            self::MEDIUM => Honor::currency(1_000_000_000),
            self::LARGE => Honor::currency(1_000_000_000_000),
        };
    }

    public function minStockAmount(): string
    {
        return $this->price()->divide(1000)->getAmount();
    }

    public function maxStockAmount(): string
    {
        return $this->price()->divide(100)->getAmount();
    }

    public function stockAmount(): string
    {
        // min / max should never be higher than PHP_INT_MAX
        return (string) Random::number(
            (int) $this->maxStockAmount(),
            (int) $this->minStockAmount()
        );
    }

    public function junkRate(?EffectCollection $effects): int
    {
        $result = 20 / $this->apply($effects);
        return (int) round($result);
    }

    public function itemRate(?EffectCollection $effects): int
    {
        $result = $this->apply($effects);
        return (int) round($result);
    }

    public function stockRate(?EffectCollection $effects): int
    {
        return 100 - $this->junkRate($effects) - $this->itemRate($effects);
    }
}