<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Raid\Raid;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RaidRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Raid::class);
    }

    public function hasActiveRaid(Chat $chat): bool
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.chat = :chat')
            ->andWhere('r.isActive = true')
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function getActiveRaid(Chat $chat): ?Raid
    {
        return $this->createQueryBuilder('r')
            ->where('r.chat = :chat')
            ->andWhere('r.isActive = true')
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getOneOrNullResult();
    }

}