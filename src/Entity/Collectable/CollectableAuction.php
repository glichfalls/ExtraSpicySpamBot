<?php

namespace App\Entity\Collectable;

use App\Entity\User\User;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

class CollectableAuction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: CollectableItemInstance::class)]
    private CollectableItemInstance $instance;

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

    public function getInstance(): CollectableItemInstance
    {
        return $this->instance;
    }

    public function setInstance(CollectableItemInstance $instance): void
    {
        $this->instance = $instance;
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