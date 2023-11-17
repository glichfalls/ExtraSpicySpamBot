<?php

namespace App\Entity\Item\Effect;

interface EffectApplicable
{
    public function getType(): EffectType;

    public function getName(): string;

    public function getOperator(): string;

    public function getMagnitude(): float;

    public function apply(int|float $value): int|float;

    public function applyNegative(int|float $value): int|float;
}