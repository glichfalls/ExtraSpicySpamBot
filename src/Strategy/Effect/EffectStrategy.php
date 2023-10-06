<?php

namespace App\Strategy\Effect;

use App\Entity\Collectable\Effect\Effect;

interface EffectStrategy
{

    public function apply(mixed $originalValue, Effect $effect): float;

}