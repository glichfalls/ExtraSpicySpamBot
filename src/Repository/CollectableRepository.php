<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\Collectable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CollectableRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collectable::class);
    }

}