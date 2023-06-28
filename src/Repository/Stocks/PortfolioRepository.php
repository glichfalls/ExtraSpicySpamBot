<?php

namespace App\Repository\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    public function getByChatAndUser(Chat $chat, User $user): ?Portfolio
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.chat = :chat')
            ->andWhere('p.user = :user')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}