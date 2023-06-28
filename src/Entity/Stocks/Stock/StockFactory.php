<?php

namespace App\Entity\Stocks\Stock;

class StockFactory
{

    public static function create(string $name, string $symbol): Stock
    {
        $stock = new Stock();
        $stock->setName($name);
        $stock->setSymbol($symbol);
        return $stock;
    }

}