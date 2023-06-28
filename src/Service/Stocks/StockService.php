<?php

namespace App\Service\Stocks;

use App\Entity\Honor\Stocks\Stock\Stock;
use App\Entity\Honor\Stocks\Stock\StockPrice;
use App\Repository\Stocks\StockRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockService
{


    public function __construct(
        private EntityManagerInterface $manager,
        private StockRepository $stockRepository,
    )
    {
    }

    public function getCurrentPrice(string $symbol): ?StockPrice
    {
        $today = new \DateTime();
        $stock = $this->stockRepository->getBySymbol($symbol);
        if ($stock !== null) {
            if ($stock->getLatestStockPrice()?->getDate()->format('Y-m-d') === $today->format('Y-m-d')) {
                return $stock->getLatestStockPrice();
            }
        } else {
            $stock = new Stock();
            $stock->setSymbol($symbol);
        }

    }

}