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

    /**
     * @param Chat $chat
     * @param User $user
     * @return CollectableItemInstance[]
     */
    public function getCurrentCollectionByChatAndUser(Chat $chat, User $user): array
    {
        return $this->createQueryBuilder('i')
            ->addSelect('t')
            ->leftJoin('i.transactions', 't')
            ->andWhere('i.chat = :chat')
            ->andWhere('t.buyer = :user')
            ->andWhere('t.id = (SELECT MAX(t2.id) FROM App\Entity\Collectable\CollectableTransaction t2 WHERE t2.instance = i.id)')
            ->setParameters([
                'chat' => $chat,
                'user' => $user,
            ])
            ->getQuery()
            ->getResult();
    }

}
