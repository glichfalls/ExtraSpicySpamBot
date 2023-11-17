<?php

namespace App\Entity\Item\Effect;

use App\Entity\User\User;

readonly class UserEffect implements EffectApplicable
{
    public function __construct(public Effect $effect, public User $user, public int $amount)
    {
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
        for ($i = 0; $i < $this->amount; $i++) {
            $value = $this->effect->apply($value);
        }
        return $value;
    }

    public function applyNegative(int|float $value): int|float
    {
        return $this->effect->applyNegative($value);
    }
}