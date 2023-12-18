<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemRarity;
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
            ->andWhere('i.expiresAt IS NULL OR i.expiresAt > :now')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function getInstancesWithoutOwnerByChat(Chat $chat, ?ItemRarity $rarity = null): array
    {
        $query = $this->createQueryBuilder('i')
            ->andWhere('i.chat = :chat')
            ->andWhere('i.owner IS NULL')
            ->andWhere('i.expiresAt IS NULL OR i.expiresAt > :now')
            ->setParameter('chat', $chat)
            ->setParameter('now', new \DateTime());

        if ($rarity) {
            $query->join('i.item', 'item')
                ->andWhere('item.rarity = :rarity')
                ->setParameter('rarity', $rarity);
        }

        return $query->getQuery()
            ->getResult();
    }

}
