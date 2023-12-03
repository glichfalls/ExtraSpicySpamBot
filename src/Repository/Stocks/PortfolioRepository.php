<?php

namespace App\Repository\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Season\Season;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getByChatAndUser(Season $season, Chat $chat, User $user): ?Portfolio
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.chat = :chat')
            ->andWhere('p.user = :user')
            ->andWhere('p.season = :season')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->setParameter('season', $season)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
