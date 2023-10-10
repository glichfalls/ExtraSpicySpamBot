<?php

namespace App\Entity\Chat;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Annotation\UserAware;
use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\ChatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity(repositoryClass: ChatRepository::class)]
#[ApiResource(
    denormalizationContext: ['groups' => ['chat:public:write']],
    normalizationContext: ['groups' => ['public:read', 'chat:public:read', 'user:read']],
    order: ['name' => 'ASC'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'name' => 'partial',
])]
#[UserAware(fieldNames: ['users'])]
class Chat
{
    use Id;
    use TimestampableEntity;

    #[Column(unique: true)]
    #[Groups(['chat:public:read'])]
    private string $chatId;

    #[Column]
    #[Groups(['chat:public:read'])]
    private string $name;

    #[OneToOne(targetEntity: ChatConfig::class, cascade: ["persist", "remove"])]
    #[Groups(['chat:public:read'])]
    private ChatConfig $config;

    #[ManyToMany(targetEntity: User::class, inversedBy: "chats", fetch: 'EXTRA_LAZY')]
    #[Groups(['chat:public:read'])]
    private Collection $users;

    #[OneToMany(mappedBy: "chat", targetEntity: Message::class, fetch: 'EXTRA_LAZY')]
    #[Groups(['message:public:read'])]
    private Collection $messages;

    public function __construct()
    {
        $this->generateId();
        $this->messages = new ArrayCollection();
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function setChatId(string $chatId): void
    {
        $this->chatId = $chatId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getConfig(): ChatConfig
    {
        return $this->config;
    }

    public function setConfig(ChatConfig $config): void
    {
        $this->config = $config;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        $this->users->add($user);
    }

    public function removeUser(User $user): void
    {
        $this->users->removeElement($user);
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): void
    {
        $this->messages->add($message);
    }

    public function removeMessage(Message $message): void
    {
        $this->messages->removeElement($message);
    }

}
