<?php

namespace App\Entity\Honor\Bank;

use Money\Money;

class TransactionFactory
{

    public static function create(Money $amount): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        return $transaction;
    }

}