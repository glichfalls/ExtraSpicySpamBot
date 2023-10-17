<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\SlotMachine\SlotMachineJackpot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SlotMachineJackpotRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlotMachineJackpot::class);
    }

    public function getActiveByChat(Chat $chat): ?SlotMachineJackpot
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.chat = :chat')
            ->andWhere('j.active = true')
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getOneOrNullResult();
    }

}