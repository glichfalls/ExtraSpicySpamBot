<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ItemInstanceRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemInstance::class);
    }

    /**
     * @param Chat $chat
     * @param User $user
     * @return ItemInstance[]
     */
    public function getCollectionByChatAndUser(Chat $chat, User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.chat = :chat')
            ->andWhere('i.owner = :user')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

}