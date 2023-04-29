<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HonorRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Honor::class);
    }

    public function getHonorCount(User $user, Chat $chat): int
    {
        return $this->createQueryBuilder('h')
            ->select('SUM(h.amount)')
            ->where('h.chat = :chat')
            ->andWhere('h.recipient = :user')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult() ?: 0;
    }

    public function getLastChange(User $sender, User $recipient, Chat $chat): ?Honor
    {
        return $this->createQueryBuilder('h')
            ->where('h.chat = :chat')
            ->andWhere('h.sender = :sender')
            ->andWhere('h.recipient = :recipient')
            ->setParameter('chat', $chat)
            ->setParameter('sender', $sender)
            ->setParameter('recipient', $recipient)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}