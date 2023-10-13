<?php

namespace App\Entity\Collectable;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
#[ApiResource]
class CollectableItemInstance
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Collectable::class, inversedBy: 'instances')]
    #[JoinColumn(nullable: false)]
    private Collectable $collectable;

    #[ManyToOne(targetEntity: Chat::class)]
    #[JoinColumn(nullable: false)]
    private Chat $chat;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'collectables')]
    private ?User $owner = null;

    #[Column(type: 'integer')]
    private int $price;

    public function __construct()
    {
        $this->generateId();
    }

    public function getCollectable(): Collectable
    {
        return $this->collectable;
    }

    public function setCollectable(Collectable $collectable): void
    {
        $this->collectable = $collectable;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

}
