<?php

namespace App\Entity\Item\Attribute;

use App\Entity\Item\Effect\EffectCollection;
use App\Utils\Random;

enum ItemRarity: string
{
    case Common = 'common';
    case Rare = 'rare';
    case Epic = 'epic';
    case Legendary = 'legendary';

    public function value(): ?int
    {
        return match ($this) {
            self::Common => null,
            self::Rare => 20,
            self::Epic => 9,
            self::Legendary => 1,
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Common => '⚪',
            self::Rare => '🔵',
            self::Epic => '🟣',
            self::Legendary => '🟠',
        };
    }

    public function name(): string
    {
        return sprintf('%s %s', $this->emoji(), $this->name);
    }

    /**
     * @return array<string, int>
     */
    public static function all(): array
    {
        return [
            self::Common->name => self::Common->value(),
            self::Epic->name => self::Epic->value(),
            self::Rare->name => self::Rare->value(),
            self::Legendary->name => self::Legendary->value(),
        ];
    }

    public static function random(
        ?EffectCollection $effects = null,
        ?ItemRarity $maxRarity = null,
        ?ItemRarity $minRarity = null,
    ): self {
        $minBase = 1;
        $maxBase = 100;
        if ($maxRarity !== null) {
            $maxBase = $maxRarity->value();
        }
        if ($minRarity !== null) {
            $minBase = $minRarity->value();
        }
        $number = Random::getNumber($effects?->applyNegative($maxBase) ?? $maxBase, $minBase);
        if ($number <= self::Legendary->value()) {
            return self::Legendary;
        }
        if ($number <= self::Epic->value()) {
            return self::Epic;
        }
        if ($number <= self::Rare->value()) {
            return self::Rare;
        }
        return self::Common;
    }

    public function higher(): ?self
    {
        return self::nextHigher($this);
    }

    public function lower(): ?self
    {
        return self::nextLower($this);
    }

    public static function nextLower(self $rarity): ?self
    {
        return match ($rarity) {
            self::Common => null,
            self::Epic => self::Common,
            self::Rare => self::Epic,
            self::Legendary => self::Rare,
        };
    }

    public static function nextHigher(self $rarity): ?self
    {
        return match ($rarity) {
            self::Common => self::Epic,
            self::Epic => self::Rare,
            self::Rare => self::Legendary,
            self::Legendary => null,
        };
    }

    public function selfAndLower(): array
    {
        $rarities = [$this];
        while ($lower = self::nextLower($this)) {
            $rarities[] = $lower;
        }
        return $rarities;
    }

}
