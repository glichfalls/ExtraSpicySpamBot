<?php

namespace App\Entity\Honor\Stocks\Stock;

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

}