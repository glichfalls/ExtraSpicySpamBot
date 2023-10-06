<?php

namespace App\Strategy\Effect;

use App\Entity\Collectable\Effect\Effect;

class LuckEffectStrategy implements EffectStrategy
{

    public function apply(mixed $originalValue, Effect $effect): float
    {
        return $originalValue * $effect->getMagnitude();
    }

}