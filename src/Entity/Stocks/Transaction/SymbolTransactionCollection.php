<?php

namespace App\Entity\Stocks\Transaction;

use App\Entity\Stocks\Stock\StockPrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class SymbolTransactionCollection extends ArrayCollection
{

    public function __construct(private string $symbol, private ?StockPrice $currentPrice, Collection $transactions)
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

    public function getCurrentPrice(): ?StockPrice
    {
        return $this->currentPrice;
    }

    public function getTotalAmount(): int
    {
        return array_reduce($this->toArray(), fn (int $carry, StockTransaction $element) => $carry + $element->getAmount(), 0);
    }

    public function getTotalBuyPrice(): float
    {
        return array_reduce($this->toArray(), fn (float $carry, StockTransaction $element) => $carry + $element->getTotal(), 0);
    }

    public function getTotalHonorBuyPrice(): float
    {
        return array_reduce($this->toArray(), fn (float $carry, StockTransaction $element) => $carry + $element->getHonorTotal(), 0);
    }

    public function getCurrentTotal(?StockPrice $stockPrice = null): float
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return 0;
        }
        $currentPrice = $this->getCurrentPrice() ?? $stockPrice;
        return $currentPrice->getPrice() * $this->getTotalAmount();
    }

    public function getCurrentHonorTotal(?StockPrice $stockPrice = null): float
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return 0;
        }
        $currentPrice = $this->getCurrentPrice() ?? $stockPrice;
        return $currentPrice->getHonorPrice() * $this->getTotalAmount();
    }

}