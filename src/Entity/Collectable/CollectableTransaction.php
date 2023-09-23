<?php

namespace App\Entity\Collectable;

use App\Entity\User\User;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class CollectableTransaction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: CollectableItemInstance::class, inversedBy: 'transactions')]
    private CollectableItemInstance $instance;

    #[ManyToOne(targetEntity: User::class)]
    private ?User $seller = null;

    #[ManyToOne(targetEntity: User::class)]
    private ?User $buyer = null;

    #[OneToOne(targetEntity: CollectableTransaction::class)]
    private ?CollectableTransaction $next = null;

    #[Column(type: 'integer')]
    private int $price;

    #[Column(type: 'boolean')]
    private bool $isCompleted = false;

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

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): void
    {
        $this->seller = $seller;
    }

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(?User $buyer): void
    {
        $this->buyer = $buyer;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): void
    {
        $this->isCompleted = $isCompleted;
    }

}