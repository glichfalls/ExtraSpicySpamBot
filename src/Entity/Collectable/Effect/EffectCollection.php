<?php

namespace App\Entity\Collectable\Effect;

use Doctrine\Common\Collections\ArrayCollection;

class EffectCollection extends ArrayCollection
{

    /**
     * @return array<Effect>
     */
    public function getValues(): array
    {
        return self::filter(fn (mixed $effect) => $effect instanceof Effect)->getValues();
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

}