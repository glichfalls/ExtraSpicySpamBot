<?php

namespace App\Repository\Stocks;

use App\Entity\Stocks\Stock\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function getBySymbol(string $symbol): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->where('s.symbol = :symbol')
            ->setParameter('symbol', $symbol)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}