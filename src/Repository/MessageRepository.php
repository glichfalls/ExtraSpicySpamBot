<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getTextOccurrencesByUsers(Chat $chat, string $query): array
    {
        return $this->createQueryBuilder('m')
            ->select([
                'count(m.id) as count',
                'u.name',
                'u.firstName',
            ])
            ->join('m.user', 'u')
            ->where('m.chat = :chat')
            ->andWhere('m.message NOT LIKE :statsPrefix')
            ->andWhere('m.message LIKE :query')
            ->setParameter('chat', $chat)
            ->setParameter('statsPrefix', '!stats%')
            ->setParameter('query', sprintf('%%%s%%', $query))
            ->groupBy('u.name, u.firstName')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTextOccurrencesByDate(Chat $chat, string $query): array
    {
        return $this->createQueryBuilder('m')
            ->select([
                'count(m.id) as count',
                'DATE(m.createdAt) as date',
            ])
            ->join('m.user', 'u')
            ->where('m.chat = :chat')
            ->andWhere('m.message NOT LIKE :statsPrefix')
            ->andWhere('m.message LIKE :query')
            ->setParameter('chat', $chat)
            ->setParameter('statsPrefix', '!stats%')
            ->setParameter('query', sprintf('%%%s%%', $query))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
