<?php

namespace App\Entity\Honor\Stocks\Transaction;

use App\Entity\Honor\Stocks\Stock\Stock;

class TransactionFactory
{

    public static function create(Stock $stock, int $amount, int $price): Transaction
    {
        $transaction = new Transaction();
        $transaction->setStock($stock);
        $transaction->setAmount($amount);
        $transaction->setPrice($price);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        return $transaction;
    }

}