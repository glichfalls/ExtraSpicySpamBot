<?php

namespace App\Strategy\Effect;

use App\Entity\Collectable\Effect\Effect;

class LuckEffectStrategy
{

    public function apply(int|float $originalValue, Effect $effect): float
    {
        return $originalValue * $effect->getMagnitude();
    }

}