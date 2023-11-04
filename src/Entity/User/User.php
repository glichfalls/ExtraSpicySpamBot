<?php

namespace App\Entity\User;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Chat\Chat;
use App\Entity\Item\ItemInstance;
use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Model\Id;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['public:read', 'user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
#[ApiFilter(SearchFilter::class)]
class User implements UserInterface
{
    use Id;
    use TimestampableEntity;

    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER = 'ROLE_USER';

    #[Column(type: 'integer')]
    #[Groups(['user:read'])]
    private int $telegramUserId;

    #[Column(type: 'string', nullable: true)]
    #[Groups(['user:read', 'collectable:read'])]
    private ?string $name = null;

    #[Column(type: 'string', nullable: true)]
    #[Groups(['user:read'])]
    private ?string $firstName = null;

    #[Column(type: 'string', nullable: true)]
    #[Groups(['user:read'])]
    private ?string $lastName = null;

    #[OneToMany(mappedBy: 'sender', targetEntity: Honor::class, fetch: 'LAZY')]
    #[Ignore]
    private Collection $sentHonor;

    #[OneToMany(mappedBy: 'recipient', targetEntity: Honor::class)]
    #[Ignore]
    private Collection $receivedHonor;

    #[OneToMany(mappedBy: 'user', targetEntity: Message::class)]
    #[Ignore]
    private Collection $messages;

    #[ManyToMany(targetEntity: Chat::class, mappedBy: "users")]
    private Collection $chats;

    #[OneToMany(mappedBy: "owner", targetEntity: ItemInstance::class)]
    #[Ignore]
    private Collection $collectables;

    #[Column(type: 'json')]
    #[Ignore]
    private array $roles = [
        self::ROLE_USER,
    ];

    public function __construct()
    {
        $this->generateId();
        $this->sentHonor = new ArrayCollection();
        $this->receivedHonor = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->chats = new ArrayCollection();
        $this->collectables = new ArrayCollection();
    }

    public function getTelegramUserId(): int
    {
        return $this->telegramUserId;
    }

    public function setTelegramUserId(int $telegramUserId): void
    {
        $this->telegramUserId = $telegramUserId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getChats(): Collection
    {
        return $this->chats;
    }

    public function addChat(Chat $chat): void
    {
        if (!$this->chats->contains($chat)) {
            $this->chats->add($chat);
        }
    }

    public function removeChat(Chat $chat): void
    {
        if ($this->chats->contains($chat)) {
            $this->chats->removeElement($chat);
        }
    }

    public function getSentHonor(): Collection
    {
        return $this->sentHonor;
    }

    public function getReceivedHonor(): Collection
    {
        return $this->receivedHonor;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return Collection<ItemInstance>
     */
    public function getCollectables(): Collection
    {
        return $this->collectables;
    }

    public function setMessages(Collection $collection): void
    {
        $this->messages = $collection;
    }

    public function getUserIdentifier(): string
    {
        return $this->getTelegramUserId();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = array_unique(array_merge($this->roles, array_values($roles)));
    }

    public function eraseCredentials(): void
    {

    }

}
