<?php

namespace App\Entity\Stocks\Transaction;

use Doctrine\Common\Collections\ArrayCollection;

class SymbolTransactionCollection extends ArrayCollection
{

    public function __construct(private string $symbol, array $elements = [])
    {
        if (array_filter($elements, fn (StockTransaction $element) => $element->getSymbol() !== $this->getSymbol()) > 0) {
            throw new \InvalidArgumentException('All elements must be of type SymbolTransaction');
        }
        parent::__construct($elements);
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getTotalAmount(): int
    {
        return array_reduce($this->toArray(), fn (int $carry, StockTransaction $element) => $carry + $element->getAmount(), 0);
    }

    public function getTotalBuyPrice(): float
    {
        return array_reduce($this->toArray(), fn (float $carry, StockTransaction $element) => $carry + $element->getTotal(), 0);
    }

    public function getTotalHonoredBuyPrice(): float
    {
        return array_reduce($this->toArray(), fn (float $carry, StockTransaction $element) => $carry + $element->getHonorTotal(), 0);
    }

}