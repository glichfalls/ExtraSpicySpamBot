<?php

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\Effect\Effect;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

class EffectRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Effect::class);
    }

    /**
     * @param User $user
     * @param Chat $chat
     * @return Collection<Effect>
     */
    public function getByUser(User $user, Chat $chat): Collection
    {
        return $this->createQueryBuilder('e')
            ->join('e.collectables', 'c')
            ->join('c.instances', 'i')
            ->join('i.owner', 'u')
            ->andWhere('u.id = :id')
            ->andWhere('i.chat = :chat')
            ->setParameter('id', $user->getId())
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @param Chat $chat
     * @param string $type
     * @return Collection<Effect>
     */
    public function getByUserAndType(User $user, Chat $chat, string $type): Collection
    {
        return $this->createQueryBuilder('e')
            ->join('e.collectables', 'c')
            ->join('c.instances', 'i')
            ->join('i.owner', 'o')
            ->andWhere('o.id = :id')
            ->andWhere('e.type = :type')
            ->andWhere('i.chat = :chat')
            ->setParameter('id', $user->getId())
            ->setParameter('type', $type)
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getResult();
    }

}
