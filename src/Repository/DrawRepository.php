<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorMillions\Draw\Draw;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

class DrawRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Draw::class);
    }

    public function getByChat(Chat $chat): ?Draw
    {
        return $this->createQueryBuilder('d')
            ->where('d.chat = :chat')
            ->setParameter('chat', $chat)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param \DateTime $date
     * @return Collection<Draw>
     */
    public function getDrawsByDate(\DateTime $date): Collection
    {
        return $this->createQueryBuilder('d')
            ->where('d.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function getByChatAndDate(Chat $chat, \DateTime $date): ?Draw
    {
        return $this->createQueryBuilder('d')
            ->where('d.chat = :chat')
            ->andWhere('d.date = :date')
            ->setParameter('chat', $chat)
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}