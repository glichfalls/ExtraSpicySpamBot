<?php

namespace App\Repository\Stocks;

use App\Entity\Stocks\Transaction\StockTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockTransactionRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockTransaction::class);
    }

}