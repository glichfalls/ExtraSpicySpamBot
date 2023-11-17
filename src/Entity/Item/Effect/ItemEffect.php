<?php

namespace App\Entity\Item\Effect;

use App\Entity\Item\Item;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class ItemEffect implements EffectApplicable
{
    use Id;

    #[ManyToOne(targetEntity: Effect::class, inversedBy: 'items')]
    private Effect $effect;

    #[ManyToOne(targetEntity: Item::class, inversedBy: 'effects')]
    private Item $item;

    #[Column(type: 'integer')]
    private int $amount = 1;

    public function __construct()
    {
        $this->generateId();
    }

    public function getEffect(): Effect
    {
        return $this->effect;
    }

    public function setEffect(Effect $effect): void
    {
        $this->effect = $effect;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getType(): EffectType
    {
        return $this->effect->getType();
    }

    public function getName(): string
    {
        return $this->effect->getName();
    }

    public function getDescription(): string
    {
        return $this->effect->getDescription();
    }

    public function getOperator(): string
    {
        return $this->effect->getOperator();
    }

    public function getMagnitude(): float
    {
        return $this->effect->getMagnitude();
    }

    public function apply(int|float $value): int|float
    {
        for ($i = 0; $i < $this->getAmount(); $i++) {
            $value = $this->effect->apply($value);
        }
        return $value;
    }

    public function applyNegative(int|float $value): int|float
    {
        return $this->effect->applyNegative($value);
    }
}