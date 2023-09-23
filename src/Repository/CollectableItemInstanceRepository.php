<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CollectableItemInstanceRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectableItemInstance::class);
    }

    public function getCurrentCollectionByChatAndUser(Chat $chat, User $user): array
    {
        return $this->createQueryBuilder('i')
            ->select('i')
            ->leftJoin('i.transactions', 't')
            ->where('i.chat = :chat')
            ->orderBy('t.createdAt', 'DESC')
            ->groupBy('i.id')
            ->having('MAX(t.createdAt) = t.createdAt AND t.owner = :user')
            ->setParameters([
                'chat' => $chat,
                'user' => $user,
            ])
            ->getQuery()
            ->getResult();
    }

}
