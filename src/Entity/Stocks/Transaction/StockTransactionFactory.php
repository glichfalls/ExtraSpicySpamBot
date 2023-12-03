<?php

namespace App\Entity\Stocks\Transaction;

use App\Entity\Honor\Season\Season;
use App\Entity\Stocks\Stock\StockPrice;

class StockTransactionFactory
{

    public static function create(Season $season, StockPrice $price, string $amount): StockTransaction
    {
        $transaction = new StockTransaction();
        $transaction->setSeason($season);
        $transaction->setPrice($price);
        $transaction->setAmount($amount);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        return $transaction;
    }

}