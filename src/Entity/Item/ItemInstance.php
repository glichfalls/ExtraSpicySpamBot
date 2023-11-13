<?php

namespace App\Entity\Item;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use App\Model\Payload;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[ApiResource(
    normalizationContext: ['groups' => [
        'public:read',
        'item:read',
        'chat:public:read',
    ]],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'item' => 'exact',
    'item.id' => 'exact',
    'chat' => 'exact',
    'chat.id' => 'exact',
])]
class ItemInstance
{
    use Id;
    use TimestampableEntity;
    use Payload;

    #[ManyToOne(targetEntity: Item::class, inversedBy: 'instances')]
    #[JoinColumn(nullable: false)]
    #[Groups(['item:read'])]
    private Item $item;

    #[ManyToOne(targetEntity: Chat::class)]
    #[JoinColumn(nullable: false)]
    #[Groups(['item:read'])]
    private Chat $chat;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'collectables')]
    #[Groups(['item:read'])]
    private ?User $owner = null;

    #[Column(type: 'boolean')]
    #[Groups(['item:read'])]
    private bool $tradeable;

    #[Column(type: 'datetime', nullable: true)]
    #[Groups(['item:read'])]
    private ?\DateTimeInterface $expiresAt = null;

    public function __construct()
    {
        $this->generateId();
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $collectable): void
    {
        $this->item = $collectable;
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

    public function isTradeable(): bool
    {
        return $this->tradeable;
    }

    public function setTradeable(bool $tradeable): void
    {
        $this->tradeable = $tradeable;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTime();
    }

}
