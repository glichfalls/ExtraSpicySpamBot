<?php

namespace App\Entity\Stocks\Transaction;

use App\Entity\Stocks\Stock\StockPrice;

class StockTransactionFactory
{

    public static function create(StockPrice $price, int $amount): StockTransaction
    {
        $transaction = new StockTransaction();
        $transaction->setPrice($price);
        $transaction->setAmount($amount);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        return $transaction;
    }

}