<?php

namespace App\Entity\Stocks\Stock;

use Finnhub\Model\SymbolLookupInfo;

class StockFactory
{

    public static function create(
        string $name,
        string $symbol,
        string $displaySymbol,
        string $type,
    ): Stock
    {
        $stock = new Stock();
        $stock->setName($name);
        $stock->setSymbol($symbol);
        $stock->setDisplaySymbol($displaySymbol);
        $stock->setType($type);
        return $stock;
    }

    public static function createFromLookupInfo(SymbolLookupInfo $info): Stock
    {
        return self::create(
            $info->getDescription(),
            $info->getSymbol(),
            $info->getDisplaySymbol(),
            $info->getType(),
        );
    }

}