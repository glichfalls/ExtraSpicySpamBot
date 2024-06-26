<?php declare(strict_types=1);

namespace App\Entity\Stocks\Stock;

use Finnhub\Model\Quote;

class StockPriceFactory
{

    public static function create(Stock $stock): StockPrice
    {
        $price = new StockPrice();
        $price->setStock($stock);
        $price->setCreatedAt(new \DateTime());
        $price->setUpdatedAt(new \DateTime());
        return $price;
    }

    public static function createFromQuote(Stock $stock, Quote $quote): StockPrice
    {
        $price = self::create($stock);
        $price->setPrice((string) $quote->getC());
        $price->setChangeAbsolute($quote->getD());
        $price->setChangePercent($quote->getDp());
        return $price;
    }

}