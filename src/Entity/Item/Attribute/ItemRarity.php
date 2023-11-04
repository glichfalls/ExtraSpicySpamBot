<?php

namespace App\Entity\Item\Attribute;

use App\Entity\Item\Effect\EffectCollection;
use App\Utils\Random;

enum ItemRarity
{
    case Common;
    case Uncommon;
    case Rare;
    case Legendary;

    public function value(): int
    {
        return match ($this) {
            self::Common => 70,
            self::Uncommon => 20,
            self::Rare => 9,
            self::Legendary => 1,
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Common => '🟦',
            self::Uncommon => '🟩',
            self::Rare => '🟨',
            self::Legendary => '🟥',
        };
    }

    /**
     * @return array<string, int>
     */
    public static function all(): array
    {
        return [
            self::Common->name => self::Common->value(),
            self::Uncommon->name => self::Uncommon->value(),
            self::Rare->name => self::Rare->value(),
            self::Legendary->name => self::Legendary->value(),
        ];
    }

    public static function random(?EffectCollection $effects = null): self
    {
        $random = Random::getNumber($effects?->apply(100) ?? 100);
        if ($random <= self::Common->value()) {
            return self::Common;
        }
        if ($random <= self::Common->value() + self::Uncommon->value()) {
            return self::Uncommon;
        }
        if ($random <= self::Common->value() + self::Uncommon->value() + self::Rare->value()) {
            return self::Rare;
        }
        return self::Legendary;
    }

}
