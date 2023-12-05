<?php declare(strict_types=1);

namespace App\Entity\Stocks\Transaction;

use App\Entity\Honor\Honor;
use App\Entity\Stocks\Stock\StockPrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Money\Money;

/**
 * @extends ArrayCollection<int, StockTransaction>
 */
class SymbolTransactionCollection extends ArrayCollection
{

    /**
     * @param string $symbol
     * @param StockPrice|null $currentPrice
     * @param Collection<int, StockTransaction> $transactions
     */
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

    /**
     * @return numeric-string
     */
    public function getTotalAmount(): string
    {
        return array_reduce($this->toArray(), fn (string $carry, StockTransaction $element) => bcadd($carry, $element->getAmount()), '0');
    }

    public function getCurrentTotal(?StockPrice $stockPrice = null): string
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return '0';
        }
        /** @var StockPrice $currentPrice */
        $currentPrice = $this->getCurrentPrice() ?? $stockPrice;
        return bcmul($currentPrice->getPrice(), $this->getTotalAmount());
    }

    public function getCurrentHonorTotal(?StockPrice $stockPrice = null): Money
    {
        if ($this->getCurrentPrice() === null && $stockPrice === null) {
            return Honor::currency(0);
        }
        /** @var StockPrice $currentPrice */
        $currentPrice = $this->getCurrentPrice() ?? $stockPrice;
        return $currentPrice->getHonorPrice()->multiply($this->getTotalAmount());
    }

}
