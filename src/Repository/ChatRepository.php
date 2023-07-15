<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    public function getChatByTelegramId(string $id): ?Chat
    {
        return $this->findOneBy(['chatId' => $id]);
    }

    /**
     * @return array<Chat>
     */
    public function getAllWithPassiveHonorEnabled(): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.config', 'cc')
            ->where('cc.passiveHonorEnabled = true')
            ->getQuery()
            ->getResult();
    }

}