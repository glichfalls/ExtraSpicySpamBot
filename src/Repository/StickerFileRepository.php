<?php

namespace App\Repository;

use App\Entity\Sticker\StickerFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StickerFileRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StickerFile::class);
    }

    public function getBySticker(string $sticker): ?StickerFile
    {
        return $this->createQueryBuilder('sf')
            ->where('sf.sticker = :sticker')
            ->setParameter('sticker', $sticker)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}