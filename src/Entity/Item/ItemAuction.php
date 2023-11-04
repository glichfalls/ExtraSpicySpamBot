<?php

namespace App\Entity\Item;

use App\Entity\User\User;
use App\Model\Id;
use App\Repository\ItemAuctionRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: ItemAuctionRepository::class)]
class ItemAuction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: ItemInstance::class)]
    private ItemInstance $instance;

    #[ManyToOne(targetEntity: User::class)]
    private ?User $seller = null;

    #[ManyToOne(targetEntity: User::class)]
    private ?User $highestBidder = null;

    #[Column(type: 'integer')]
    private int $highestBid = 0;

    #[Column(type: 'boolean')]
    private bool $active = true;

    public function __construct()
    {
        $this->generateId();
    }

    public function getInstance(): ItemInstance
    {
        return $this->instance;
    }

    public function setInstance(ItemInstance $instance): void
    {
        $this->instance = $instance;
    }

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): void
    {
        $this->seller = $seller;
    }

    public function getHighestBidder(): ?User
    {
        return $this->highestBidder;
    }

    public function setHighestBidder(?User $highestBidder): void
    {
        $this->highestBidder = $highestBidder;
    }

    public function getHighestBid(): int
    {
        return $this->highestBid;
    }

    public function setHighestBid(int $highestBid): void
    {
        $this->highestBid = $highestBid;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

}