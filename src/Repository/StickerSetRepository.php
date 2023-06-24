<?php

namespace App\Repository;

use App\Entity\Sticker\StickerSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StickerSetRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StickerSet::class);
    }

    public function getByTitleOrNull(string $title): ?StickerSet
    {
        return $this->createQueryBuilder('s')
            ->where('s.title = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}