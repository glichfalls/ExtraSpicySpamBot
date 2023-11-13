<?php

namespace App\Entity\Item\Attribute;

use App\Entity\Item\Effect\EffectCollection;
use App\Utils\Random;

enum ItemRarity: string implements \JsonSerializable
{
    case Common = 'common';
    case Rare = 'rare';
    case Epic = 'epic';
    case Legendary = 'legendary';

    public function value(): int
    {
        return match ($this) {
            self::Common => 100,
            self::Rare => 40,
            self::Epic => 10,
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
        $min = 1;
        $max = 100;
        if ($maxRarity !== null) {
            $higher = $maxRarity->higher();
            if ($higher !== null) {
                $min = $higher->value() + 1;
            } else {
                $min = self::Legendary->value();
            }
        }
        if ($minRarity !== null) {
            $max = $minRarity->value();
        }
        $number = Random::number($max, $min);
        if ($effects !== null) {
            $numberWithAppliedEffects = $effects->applyNegative($number);
            if ($numberWithAppliedEffects > $max) {
                $number = $max;
            } elseif ($numberWithAppliedEffects < $min) {
                $number = $min;
            } else {
                $number = $numberWithAppliedEffects;
            }
        }
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
            self::Rare => self::Common,
            self::Epic => self::Rare,
            self::Legendary => self::Epic,
        };
    }

    public static function nextHigher(self $rarity): ?self
    {
        return match ($rarity) {
            self::Common => self::Rare,
            self::Rare => self::Epic,
            self::Epic => self::Legendary,
            self::Legendary => null,
        };
    }

    public function selfAndLower(): array
    {
        $rarities = [$this];
        $lower = $this->lower();
        while ($lower !== null) {
            $rarities[] = $lower;
            $lower = $lower->lower();
        }
        return $rarities;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name,
            'label' => $this->name(),
            'value' => $this->value(),
            'emoji' => $this->emoji(),
        ];
        if ($this->higher() !== null) {
            $data['higher'] = [
                'name' => $this->higher()->name,
                'emoji' => $this->higher()->emoji(),
            ];
        } else {
            $data['higher'] = null;
        }
        if ($this->lower() !== null) {
            $data['lower'] = [
                'name' => $this->lower()->name,
                'emoji' => $this->lower()->emoji(),
            ];
        } else {
            $data['lower'] = null;
        }
        return $data;
    }

}
