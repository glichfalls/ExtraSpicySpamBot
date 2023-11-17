<?php

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\Effect\ItemEffect;
use App\Entity\Item\Effect\UserEffect;
use App\Entity\User\User;
use App\Repository\EffectRepository;
use Doctrine\Common\Collections\ArrayCollection;
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
        $result = $this->manager->getRepository(ItemEffect::class)->createQueryBuilder('ie')
            ->select('e', 'count(ie) as amount')
            ->join('ie.item', 'i')
            ->join('ie.effect', 'e')
            ->join('i.instances', 'ii')
            ->where('ii.chat = :chat')
            ->andWhere('ii.owner = :user')
            ->andWhere('e.type IN (:types)')
            ->groupBy('e')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->setParameter('types', $types)
            ->getQuery()
            ->getResult();
        $collection = new EffectCollection();
        foreach ($result as $row) {
            $effect = $row['effect'];
            $collection->add(new UserEffect($effect, $user, $row['amount']));
        }
        return $collection;
    }

}
