<?php

namespace App\Repository;

use App\Entity\OneToHowMuch\OneToHowMuchRound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OneToHowMuchRound|null find($id, $lockMode = null, $lockVersion = null)
 */
class OneToHowMuchRoundRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OneToHowMuchRound::class);
    }

}