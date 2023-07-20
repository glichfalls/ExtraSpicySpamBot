<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getByTelegramId(int $telegramUserId): ?User
    {
        return $this->findOneBy(['telegramUserId' => $telegramUserId]);
    }

    public function getByName(string $name): ?User
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function getByFirstName(Chat $chat, string $firstName): ?User
    {
        return $this->createQueryBuilder('u')
            ->join('u.messages', 'm')
            ->join('m.chat', 'c')
            ->where('c.id = :id')
            ->andWhere('u.firstName = :firstName')
            ->setParameter('id', $chat->getId())
            ->setParameter('firstName', $firstName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getUsersByChat(Chat $chat): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.messages', 'm')
            ->join('m.chat', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $chat->getId())
            ->getQuery()
            ->getResult();
    }

}