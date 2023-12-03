<?php declare(strict_types=1);

namespace App\Entity\Item\Effect;

interface EffectApplicable
{
    public function getType(): EffectType;

    public function getName(): string;

    public function getDescription(): string;

    public function getOperator(): string;

    public function getMagnitude(): string;

    public function apply(string $value): string;

    public function applyNegative(string $value): string;
}