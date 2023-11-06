<?php

namespace App\Service\Items;

use App\Entity\Item\ItemAuction;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Repository\ItemAuctionRepository;
use App\Service\HonorService;
use Doctrine\ORM\EntityManagerInterface;

readonly class ItemTradeService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private HonorService $honorService,
        private ItemAuctionRepository $auctionRepository,
    ) {
    }

    public function getActiveAuction(ItemInstance $instance): ?ItemAuction
    {
        return $this->auctionRepository->findOneBy([
            'instance' => $instance,
            'active' => true,
        ]);
    }

    public function createAuction(ItemInstance $instance): ItemAuction
    {
        if ($instance->getOwner() === null) {
            throw new \RuntimeException('Item has no owner.');
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
        $this->transferItem($auction->getInstance(), $buyer);
        $auction->setActive(false);
        $auction->setUpdatedAt(new \DateTime());
        $this->manager->flush();
    }

    public function declineItemAuction(ItemInstance $instance, User $owner): void
    {
        if ($instance->getOwner() !== $owner) {
            throw new \RuntimeException('Auction can only be declined by owner.');
        }
        $auction = $this->getActiveAuction($instance);
        if ($auction === null) {
            throw new \RuntimeException('No active auction found.');
        }
        $auction->setActive(false);
        $auction->setUpdatedAt(new \DateTime());
        $this->manager->flush();
    }

    public function transferItem(ItemInstance $instance, User $user): void
    {
        if (!$instance->hasPayloadValue('owner_history')) {
            $instance->setPayloadValue('owner_history', []);
        }
        $instance->setPayloadValue('owner_history', array_merge(
            $instance->getPayloadValue('owner_history'),
            [
                [
                    'user' => $instance->getOwner()->getId(),
                    'date' => $instance->getUpdatedAt()->format('Y-m-d H:i:s'),
                ],
            ],
        ));
        $instance->setOwner($user);
        $this->manager->flush();
    }

}