<?php

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Repository\EffectRepository;
use App\Repository\ItemInstanceRepository;
use App\Repository\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class ItemService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private ItemRepository $itemRepository,
        private ItemInstanceRepository $instanceRepository,
        private EffectRepository $effectRepository,
    ) {
    }

    public function getInstance(string $id): ?ItemInstance
    {
        return $this->instanceRepository->find($id);
    }

    public function getAvailableItems(): array
    {
        return $this->itemRepository->findAll();
    }

    /**
     * @param Chat $chat
     * @param User $user
     * @return Collection<ItemInstance>
     */
    public function getInstanceCollection(Chat $chat, User $user): Collection
    {
        return new ArrayCollection($this->instanceRepository->getCollectionByChatAndUser($chat, $user));
    }

}
