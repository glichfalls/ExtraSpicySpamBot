<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
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

    public function getChatByUser(User $user): ?Chat
    {
        return $this->createQueryBuilder('c')
            ->join('c.messages', 'm')
            ->join('m.user', 'u')
            ->andWhere('c.name = :name')
            ->andWhere('u.id = :id')
            ->setParameter('name', $user->getName())
            ->setParameter('id', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
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