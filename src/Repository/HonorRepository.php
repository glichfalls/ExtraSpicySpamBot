<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\UnexpectedResultException;
use Doctrine\Persistence\ManagerRegistry;

class HonorRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        /** @phpstan-ignore-next-line */
        parent::__construct($registry, Honor::class);
    }

    /**
     * @throws UnexpectedResultException
     */
    public function getHonorCount(User $user, Chat $chat): int
    {
        $queryBuilder = $this->createQueryBuilder('h');
        $queryBuilder
            ->select('SUM(h.amount)')
            ->where('h.recipient = :user')
            ->andWhere('h.chat = :chat')
            ->setParameter('user', $user)
            ->setParameter('chat', $chat);
        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getLeaderboard(Chat $chat): array
    {
        return $this->createQueryBuilder('h')
            ->select('r.id, r.name, r.firstName, SUM(h.amount) as amount')
            ->join('h.recipient', 'r')
            ->where('h.chat = :chat')
            ->setParameter('chat', $chat)
            ->groupBy('r.id')
            ->orderBy('amount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws UnexpectedResultException
     */
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