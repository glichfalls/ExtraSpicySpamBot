<?php

namespace App\Repository;

use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ItemRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function getAllPermanentItems(): array
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.permanent = true')
            ->getQuery();
        return $query->getResult();
    }

    /**
     * @param ItemRarity $rarity
     * @return Item[]
     */
    public function getByRarity(ItemRarity $rarity): array
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.rarity = :rarity')
            ->setParameter('rarity', $rarity)
            ->getQuery();
        return $query->getResult();
    }

}