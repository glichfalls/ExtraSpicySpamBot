<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Upgrade\HonorUpgrade;
use App\Entity\Honor\Upgrade\UpgradeType;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HonorUpgradeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HonorUpgrade::class);
    }

    public function getUpgradeByChatAndUser(Chat $chat, User $user, UpgradeType $type): ?HonorUpgrade
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.chat = :chat')
            ->andWhere('h.user = :user')
            ->andWhere('h.type = :type')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getUpgradesByChatAndUser(Chat $chat, User $user): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.chat = :chat')
            ->andWhere('h.user = :user')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

}