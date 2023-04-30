<?php

namespace App\Repository;

use App\Entity\Subscription\ChatSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatSubscriptionRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatSubscription::class);
    }

    /**
     * @param string $type
     * @return array<int, ChatSubscription>
     */
    public function getByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    public function deleteByChatId(string $chatId): void
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->getQuery()
            ->execute();
    }

}