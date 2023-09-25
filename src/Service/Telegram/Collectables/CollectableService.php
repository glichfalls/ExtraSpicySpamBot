<?php

namespace App\Service\Telegram\Collectables;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\Collectable;
use App\Entity\Collectable\CollectableAuction;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\User\User;
use App\Repository\CollectableAuctionRepository;
use App\Repository\CollectableItemInstanceRepository;
use App\Repository\CollectableRepository;
use App\Service\HonorService;
use Doctrine\ORM\EntityManagerInterface;

class CollectableService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private CollectableRepository $collectableRepository,
        private CollectableItemInstanceRepository $instanceRepository,
        private CollectableAuctionRepository $auctionRepository,
        private HonorService $honorService,
    ) {
    }

    /**
     * @return array|Collectable[]
     */
    public function getInstancableCollectables(): array
    {
        $collectables = $this->collectableRepository->findAll();
        return array_filter($collectables, fn (Collectable $collectable) => $collectable->isInstancable());
    }

    public function getInstanceById(string $id): ?CollectableItemInstance
    {
        return $this->instanceRepository->find($id);
    }

    public function acceptAuction(CollectableAuction $auction): void
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

    public function createCollectableInstance(Collectable $collectable, Chat $chat, ?User $user): CollectableItemInstance
    {
        if ($collectable->isUnique() && $collectable->getInstances()->count() > 0) {
            throw new \RuntimeException('Collectable is unique');
        }
        $instance = new CollectableItemInstance();
        $instance->setChat($chat);
        $instance->setCollectable($collectable);
        $instance->setCreatedAt(new \DateTime());
        $instance->setUpdatedAt(new \DateTime());
        $instance->setOwner($user);
        $this->manager->persist($instance);
        return $instance;
    }

    public function createAuction(CollectableItemInstance $instance): CollectableAuction
    {
        if (!$instance->getCollectable()->isTradeable()) {
            throw new \RuntimeException('Collectable is not tradeable.');
        }
        $auction = new CollectableAuction();
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

    public function getActiveAuction(CollectableItemInstance $instance): ?CollectableAuction
    {
        return $this->auctionRepository->findOneBy([
            'instance' => $instance,
            'active' => true,
        ]);
    }

    public function getCollectablesByUser(Chat $chat, User $user): array
    {

    }

}
