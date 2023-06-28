<?php

namespace App\Entity\Stocks\Transaction;

use App\Entity\Stocks\Stock\Stock;

class StockTransactionFactory
{

    public static function create(Stock $stock, int $amount, int $price): StockTransaction
    {
        $transaction = new StockTransaction();
        $transaction->setStock($stock);
        $transaction->setAmount($amount);
        $transaction->setPrice($price);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        return $transaction;
    }

}