<?php declare(strict_types=1);

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemAttribute;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\Effect\ItemEffect;
use App\Entity\Item\Item;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Repository\EffectRepository;
use App\Repository\ItemInstanceRepository;
use App\Repository\ItemRepository;
use App\Utils\Random;
use App\Utils\RateLimitUtils;
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

    public function getItemsByMaxRarity(ItemRarity $maxRarity, bool $permanent): array
    {
        $query = $this->itemRepository->createQueryBuilder('i')
            ->andWhere('i.permanent = :permanent')
            ->setParameter('permanent', $permanent);
        $expr = $query->expr()->orX();
        foreach ($maxRarity->selfAndLower() as $index => $rarity) {
            $expr->add($query->expr()->eq('i.rarity', ":rarity$index"));
            $query->setParameter("rarity$index", $rarity);
        }
        return $query->andWhere($expr)->getQuery()->getResult();
    }

    public function getRandomLoanedItemByMaxRarity(ItemRarity $rarity): Item
    {
        $items = $this->getItemsByMaxRarity($rarity, false);
        if (empty($items)) {
            throw new \RuntimeException(sprintf('No items found for rarity %s', $rarity->name()));
        }
        $exact = (new ArrayCollection($items))->filter(fn (Item $item) => $item->getRarity() === $rarity);
        if ($exact->isEmpty()) {
            return Random::arrayElement($items);
        }
        return Random::arrayElement($exact->toArray());
    }

    /**
     * @param Chat $chat
     * @param ItemRarity|null $rarity
     * @return Collection<ItemInstance>
     */
    public function getAvailableInstances(Chat $chat, ?ItemRarity $rarity = null): Collection
    {
        return new ArrayCollection($this->instanceRepository->getInstancesWithoutOwnerByChat($chat, $rarity));
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
            $now = new \DateTime(timezone: new \DateTimeZone($instance->getChat()->getConfig()->getTimezone()));
            if ($lastExecution->format('Y-m-d') === $now->format('Y-m-d')) {
                throw new \RuntimeException('This item can only be used once per day.');
            }
        }
    }

}
