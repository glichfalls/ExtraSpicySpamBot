<?php

namespace App\Entity\Stocks\Transaction;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class SymbolTransactionCollection extends ArrayCollection
{

    public function __construct(private string $symbol, Collection $transactions = null)
    {
        if ($transactions->filter(fn (StockTransaction $element) => $element->getPrice()->getStock()->getSymbol() !== $this->getSymbol()) > 0) {
            throw new \InvalidArgumentException('All elements must be of type SymbolTransaction');
        }
        parent::__construct($transactions->toArray());
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