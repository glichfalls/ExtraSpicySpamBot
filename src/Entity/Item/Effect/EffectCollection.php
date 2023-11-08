<?php

namespace App\Entity\Item\Effect;

use Doctrine\Common\Collections\ArrayCollection;

class EffectCollection extends ArrayCollection
{

    /**
     * @return array<Effect>
     */
    public function getValues(): array
    {
        return array_filter(parent::getValues(), fn ($value) => $value instanceof Effect);
    }

    /**
     * returns the input number with all applied effects
     */
    public function apply(int|float $number): int|float
    {
        foreach ($this->getValues() as $effect) {
            $number = $effect->apply($number);
        }
        return $number;
    }

    public function applyNegative(int|float $number): int|float
    {
        foreach ($this->getValues() as $effect) {
            $number = $effect->applyNegative($number);
        }
        return $number;
    }

}
