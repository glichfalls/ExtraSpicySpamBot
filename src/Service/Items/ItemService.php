<?php

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemAttribute;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Challenge\ItemChallenge;
use App\Entity\Item\Challenge\ItemChallengeFactory;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Repository\EffectRepository;
use App\Repository\ItemInstanceRepository;
use App\Repository\ItemRepository;
use App\Utils\RateLimitUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class ItemService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private ItemRepository $itemRepository,
        private ItemInstanceRepository $instanceRepository,
        private EffectRepository $effectRepository,
    ) {
    }

    public function getInstance(string $id): ItemInstance
    {
        return $this->instanceRepository->find($id) ?? throw new \InvalidArgumentException('Item instance not found.');
    }

    public function getItems(): array
    {
        return $this->itemRepository->findAll();
    }

    public function getAvailableInstances(Chat $chat, ?ItemRarity $rarity = null): array
    {
        $query = [
            'chat' => $chat,
            'owner' => null,
        ];
        if ($rarity !== null) {
            $query['rarity'] = $rarity;
        }
        return $this->instanceRepository->findBy($query);
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

    public function validateItemExecution(ItemInstance $instance, User $invoker): void
    {
        if ($instance->getOwner() !== $invoker) {
            throw new \RuntimeException('You are not the owner of this item.');
        }
        if (!$instance->getItem()->hasAttribute(ItemAttribute::Executable)) {
            throw new \RuntimeException('This item cannot be challenged.');
        }
        if ($instance->isExpired()) {
            throw new \RuntimeException('This item is expired.');
        }
        if (!$instance->hasPayloadValue('executable_name')) {
            throw new \RuntimeException('This item cannot be executed.');
        }
        $lastExecution = $instance->getPayloadValue('last_execution');
        if ($lastExecution !== null) {
            $lastExecution = new \DateTime($lastExecution);
            if (RateLimitUtils::getDaysFrom($lastExecution) < 1) {
                throw new \RuntimeException('This item can only be used once per day.');
            }
        }
    }

}