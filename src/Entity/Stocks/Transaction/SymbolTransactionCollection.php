<?php declare(strict_types=1);

namespace App\Entity\Stocks\Transaction;

use App\Entity\Honor\Honor;
use App\Entity\Stocks\Stock\StockPrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Money\Money;

class SymbolTransactionCollection extends ArrayCollection
{

    public function __construct(private readonly string $symbol, private readonly ?StockPrice $currentPrice, Collection $transactions)
    {
        parent::__construct(
            $transactions
            ->filter(fn (StockTransaction $element) => $element->getPrice()->getStock()->getSymbol() === $this->getSymbol())
            ->toArray()
        );
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getCurrentPrice(): ?StockPrice
    {
        return $this->currentPrice;
    }

    public function getTotalAmount(): string
    {
        return array_reduce($this->toArray(), fn (string $carry, StockTransaction $element) => bcadd($carry, $element->getAmount()), '0');
    }

    public function getTotalBuyPrice(): string
    {
        return array_reduce($this->toArray(), fn (string $carry, StockTransaction $element) => bcadd($carry, $element->getTotal()), '0');
    }

    public function getCurrentTotal(?StockPrice $stockPrice = null): string
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return '0';
        }
        $currentPrice = $this->getCurrentPrice() ?? $stockPrice;
        return bcmul($currentPrice->getPrice(), $this->getTotalAmount());
    }

    public function getCurrentHonorTotal(?StockPrice $stockPrice = null): Money
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return Honor::currency(0);
        }
        $currentPrice = $this->getCurrentPrice() ?? $stockPrice;
        return $currentPrice->getHonorPrice()->multiply($this->getTotalAmount());
    }

    public function getTotalProfit(?StockPrice $stockPrice = null): string
    {
        return bcsub($this->getCurrentTotal($stockPrice), $this->getTotalBuyPrice());
    }

    public function getAverageBuyPrice(): string
    {
        return bcdiv($this->getTotalBuyPrice(), $this->getTotalAmount());
    }

    public function getDailyProfit(?StockPrice $stockPrice = null): string
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return '0';
        }
        return bcmul($this->getTotalAmount(), (string) $stockPrice->getChangeAbsolute());
    }

    public function getDailyProfitPercent(?StockPrice $stockPrice = null): float
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return 0;
        }
        return $stockPrice->getChangePercent();
    }

}
