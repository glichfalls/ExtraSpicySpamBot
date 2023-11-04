<?php

namespace App\Service\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\Effect;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\Item;
use App\Entity\Item\ItemAuction;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Repository\ItemAuctionRepository;
use App\Repository\ItemInstanceRepository;
use App\Repository\ItemRepository;
use App\Repository\EffectRepository;
use App\Service\HonorService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

readonly class CollectableService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private ItemRepository $collectableRepository,
        private ItemInstanceRepository $instanceRepository,
        private ItemAuctionRepository $auctionRepository,
        private HonorService $honorService,
        private EffectRepository $effectRepository,
    ) {
    }

    /**
     * @return array|Item[]
     */
    public function getInstancableCollectables(): array
    {
        $collectables = $this->collectableRepository->findAll();
        return array_filter($collectables, fn (Item $collectable) => $collectable->isInstancable());
    }

    public function getAvailablePermanentInstances(): Collection
    {
        $items = $this->collectableRepository->findBy([
            'permanent' => true,
        ]);
        return new ArrayCollection($items);
    }

    public function getAvailableTemporaryItems(): Collection
    {
        $items = $this->collectableRepository->findBy([
            'permanent' => false,
        ]);
    }

    /**
     * @return ItemInstance[]
     */
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

    public function getInstanceById(string $id): ?ItemInstance
    {
        return $this->instanceRepository->find($id);
    }

    public function acceptAuction(ItemAuction $auction): void
    {
        if (!$auction->isActive()) {
            throw new \RuntimeException('Auction is not active.');
        }
        if ($auction->getHighestBidder() === null) {
            throw new \RuntimeException('No bids on auction.');
        }
        $chat = $auction->getInstance()->getChat();
        $buyer = $auction->getHighestBidder();
        $buyerHonor = $this->honorService->getCurrentHonorAmount($chat, $buyer);
        if ($buyerHonor < $auction->getHighestBid()) {
            throw new \RuntimeException('Buyer does not have enough honor.');
        }
        $this->honorService->removeHonor($chat, $buyer, $auction->getHighestBid());
        $this->honorService->addHonor($chat, $auction->getSeller(), $auction->getHighestBid());
        $auction->getInstance()->setOwner($buyer);
        $auction->setActive(false);
        $auction->setUpdatedAt(new \DateTime());
        $this->manager->flush();
    }

    public function createCollectableInstance(Item $collectable, Chat $chat, ?User $user): ItemInstance
    {
        if ($collectable->isUnique() && $collectable->getInstances()->count() > 0) {
            throw new \RuntimeException('Item is unique');
        }
        $instance = new ItemInstance();
        $instance->setChat($chat);
        $instance->setItem($collectable);
        $instance->setCreatedAt(new \DateTime());
        $instance->setUpdatedAt(new \DateTime());
        $instance->setOwner($user);
        $this->manager->persist($instance);
        return $instance;
    }

    public function createAuction(ItemInstance $instance): ItemAuction
    {
        if (!$instance->getItem()->isTradeable()) {
            throw new \RuntimeException('Item is not tradeable.');
        }
        $auction = new ItemAuction();
        $auction->setInstance($instance);
        $auction->setSeller($instance->getOwner());
        $auction->setHighestBidder(null);
        $auction->setHighestBid(0);
        $auction->setActive(true);
        $auction->setCreatedAt(new \DateTime());
        $auction->setUpdatedAt(new \DateTime());
        $this->manager->persist($auction);
        $this->manager->flush();
        return $auction;
    }

    public function buyCollectable(ItemInstance $instance, User $user): void
    {
        if (!$instance->getItem()->isTradeable()) {
            throw new \RuntimeException('Item is not tradeable.');
        }
        if ($instance->getOwner() !== null) {
            throw new \RuntimeException('Item is not for sale.');
        }
        $honor = $this->honorService->getCurrentHonorAmount($instance->getChat(), $user);
        if ($instance->getPrice() > $honor) {
            throw new \RuntimeException('You don\'t have enough Ehre.');
        }
        $this->honorService->removeHonor($instance->getChat(), $user, $instance->getPrice());
        $instance->setOwner($user);
        $this->manager->flush();
    }

    public function getActiveAuction(ItemInstance $instance): ?ItemAuction
    {
        return $this->auctionRepository->findOneBy([
            'instance' => $instance,
            'active' => true,
        ]);
    }

    public function getCollection(Chat $chat, User $user): array
    {
        return $this->instanceRepository->getCollectionByChatAndUser($chat, $user);
    }

    /**
     * @return Collection<Effect>
     */
    public function getEffectsByUser(User $user, Chat $chat): Collection
    {
        return $this->effectRepository->getByUser($user, $chat);
    }

    /**
     * @param User $user
     * @param Chat $chat
     * @param array<EffectType> $types
     * @return EffectCollection
     */
    public function getEffectsByUserAndType(User $user, Chat $chat, array $types): EffectCollection
    {
        return new EffectCollection($this->effectRepository->getByUserAndTypes(
            $user,
            $chat,
            array_map(fn (EffectType $type) => $type->value, $types),
        ));
    }

}
