<?php

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\User\User;
use App\Repository\EffectRepository;

readonly class ItemEffectService
{

    public function __construct(private EffectRepository $effectRepository)
    {

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
        return new EffectCollection($this->effectRepository->getByUserAndTypes($user, $chat, $types));
    }

}