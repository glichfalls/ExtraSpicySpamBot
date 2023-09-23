<?php

namespace App\Service\Telegram\Collectables;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\Collectable;
use App\Entity\Collectable\CollectableAuction;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\Collectable\CollectableTransaction;
use App\Entity\User\User;
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

    public function acceptAuction(CollectableAuction $auction): CollectableTransaction
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
        $transaction = new CollectableTransaction();
        $transaction->setInstance($auction->getInstance());
        $transaction->setPrice($auction->getHighestBid());
        $transaction->setIsCompleted(true);
        $transaction->setSeller($auction->getSeller());
        $transaction->setBuyer($auction->getHighestBidder());
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        $this->manager->persist($transaction);
        $auction->setActive(false);
        $auction->setUpdatedAt(new \DateTime());
        $this->manager->flush();
        return $transaction;
    }

    private function transferInstance(CollectableItemInstance $instance, User $buyer, int $price): CollectableTransaction
    {
        if ($instance->getOwner() !== null) {
            throw new \RuntimeException('Instance already has an owner');
        }
        $transaction = new CollectableTransaction();
        $transaction->setInstance($instance);
        $transaction->setPrice($price);
        $transaction->setIsCompleted(true);
        $transaction->setSeller(null);
        $transaction->setBuyer($buyer);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        $this->manager->persist($transaction);
        $this->manager->flush();
        return $transaction;
    }

    public function createCollectableInstance(Collectable $collectable, Chat $chat, ?User $user, int $price = 0): CollectableItemInstance
    {
        if ($collectable->isUnique() && $collectable->getInstances()->count() > 0) {
            throw new \RuntimeException('Collectable is unique');
        }
        $instance = new CollectableItemInstance();
        $instance->setChat($chat);
        $instance->setCollectable($collectable);
        $instance->setCreatedAt(new \DateTime());
        $instance->setUpdatedAt(new \DateTime());
        $transaction = $this->transferInstance($instance, $user, $price);
        $instance->getTransactions()->add($transaction);
        $this->manager->persist($instance);
        return $instance;
    }

}
