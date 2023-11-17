<?php

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\Effect\ItemEffect;
use App\Entity\User\User;
use App\Repository\EffectRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ItemEffectService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private EffectRepository $effectRepository,
    ) {
    }

    /**
     * @param User $user
     * @param Chat $chat
     * @param array<EffectType>|EffectType $types
     * @return EffectCollection
     */
    public function getEffectsByUserAndType(User $user, Chat $chat, array|EffectType $types): EffectCollection
    {
        if (!is_array($types)) {
            $types = [$types];
        }
        $effects = $this->manager->getRepository(ItemEffect::class)->createQueryBuilder('ie')
            ->join('ie.item', 'i')
            ->join('ie.effect', 'e')
            ->join('i.instances', 'ii')
            ->where('ii.chat = :chat')
            ->andWhere('ii.owner = :user')
            ->andWhere('e.type IN (:types)')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->setParameter('types', $types)
            ->getQuery()
            ->getResult();
        return new EffectCollection($effects);
    }

}