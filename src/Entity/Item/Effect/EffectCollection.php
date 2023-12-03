<?php declare(strict_types=1);

namespace App\Entity\Item\Effect;

use Doctrine\Common\Collections\ArrayCollection;
use Money\Money;

/**
 * @method EffectApplicable first()
 */
class EffectCollection extends ArrayCollection
{

    /**
     * @return array<EffectApplicable>
     */
    public function getValues(): array
    {
        return array_filter(parent::getValues(), fn ($value) => $value instanceof EffectApplicable);
    }

    /**
     * returns the input number with all applied effects
     */
    public function apply(string $number, ?string $min = null, ?string $max = null): string
    {
        foreach ($this->getValues() as $effect) {
            $number = $effect->apply($number);
        }
        if ($min !== null && $number < $min) {
            return $min;
        }
        if ($max !== null && $number > $max) {
            return $max;
        }
        return $number;
    }

    public function applyMoney(Money $money): Money
    {
        $amount = $this->apply($money->getAmount());
        return new Money($amount, $money->getCurrency());
    }

    public function applyNegative(int|float $number): int|float
    {
        foreach ($this->getValues() as $effect) {
            $number = $effect->applyNegative($number);
        }
        return $number;
    }

}
