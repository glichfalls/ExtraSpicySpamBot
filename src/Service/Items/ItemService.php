<?php

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemAttribute;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Challenge\ItemChallenge;
use App\Entity\Item\Challenge\ItemChallengeFactory;
use App\Entity\Item\Item;
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

    public function getItemsByRarity(ItemRarity $rarity): array
    {
        return $this->itemRepository->findBy(['rarity' => $rarity]);
    }

    public function getItemsByMaxRarity(ItemRarity $maxRarity): array
    {
        return $this->itemRepository->createQueryBuilder('i')
            ->where('i.rarity IN :rarities')
            ->andWhere('i.permanent = :permanent')
            ->setParameter('permanent', true)
            ->setParameter('rarities', $maxRarity->selfAndLower())
            ->getQuery()
            ->getResult();
    }

    public function getRandomItemByRarity(ItemRarity $rarity): Item
    {
        $items = $this->getItemsByMaxRarity($rarity);
        if (empty($items)) {
            throw new \RuntimeException(sprintf('No items found for rarity %s', $rarity->name()));
        }
        return $items[array_rand($items)];
    }

    /**
     * @param Chat $chat
     * @param ItemRarity|null $rarity
     * @return Collection<ItemInstance>
     */
    public function getAvailableInstances(Chat $chat, ?ItemRarity $rarity = null): Collection
    {
        $query = [
            'chat' => $chat,
            'owner' => null,
        ];
        if ($rarity !== null) {
            $query['rarity'] = $rarity;
        }
        return new ArrayCollection($this->instanceRepository->findBy($query));
    }

    public function getAvailableInstancesByMaxRarity(Chat $chat, ItemRarity $maxRarity): Collection
    {
        $data = $this->instanceRepository->createQueryBuilder('i')
            ->join('i.item', 'item')
            ->where('i.chat = :chat')
            ->andWhere('i.owner IS NULL')
            ->andWhere('item.rarity IN :rarities')
            ->setParameter('chat', $chat)
            ->setParameter('rarities', $maxRarity->selfAndLower())
            ->getQuery()
            ->getResult();
        return new ArrayCollection($data);
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
            throw new \RuntimeException('This item cannot be executed.');
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
