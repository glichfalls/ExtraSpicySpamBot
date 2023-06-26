<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Bank\BankAccount;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BankAccountRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    public function getByChatAndUser(Chat $chat, User $user): ?BankAccount
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.chat = :chat')
            ->andWhere('b.user = :user')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}