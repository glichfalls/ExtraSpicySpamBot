<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Raid\Raid;
use App\Entity\User\User;
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

    public function getLatestRaid(Chat $chat): ?Raid
    {
        return $this->createQueryBuilder('r')
            ->where('r.chat = :chat')
            ->setParameter('chat', $chat)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getLatestRaidByLeader(Chat $chat, User $leader): ?Raid
    {
        return $this->createQueryBuilder('r')
            ->where('r.chat = :chat')
            ->andWhere('r.leader = :leader')
            ->setParameter('chat', $chat)
            ->setParameter('leader', $leader)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}