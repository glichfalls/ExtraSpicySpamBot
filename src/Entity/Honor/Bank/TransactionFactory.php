<?php

namespace App\Entity\Honor\Bank;

class TransactionFactory
{

    public static function create(int $amount): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        return $transaction;
    }

}