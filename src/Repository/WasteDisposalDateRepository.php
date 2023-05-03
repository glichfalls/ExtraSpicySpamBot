<?php

namespace App\Repository;

use App\Entity\WasteDisposal\WasteDisposalDate;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WasteDisposalDateRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WasteDisposalDate::class);
    }

    /**
     * @param DateTime $date
     * @return array<int, WasteDisposalDate>
     */
    public function getAllByDate(DateTime $date): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.date = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param DateTime $date
     * @param string $zipCode
     * @return array<int, WasteDisposalDate>
     */
    public function getAllByDateAndZipCode(DateTime $date, string $zipCode): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.date = :date')
            ->andWhere('w.zipCode = :zipCode')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('zipCode', $zipCode)
            ->getQuery()
            ->getResult();
    }

}