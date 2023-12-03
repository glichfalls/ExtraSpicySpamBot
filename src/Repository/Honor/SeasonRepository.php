<?php declare(strict_types=1);

namespace App\Repository\Honor;

use App\Entity\Honor\Season\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class SeasonRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function fetchCurrentSeason(): ?Season
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.end IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
