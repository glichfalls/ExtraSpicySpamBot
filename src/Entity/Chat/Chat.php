<?php

namespace App\Entity\Chat;


use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Message\Message;
use App\Model\Id;
use App\Repository\ChatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: ChatRepository::class)]
#[ApiResource]
class Chat
{
    use Id;
    use TimestampableEntity;

    #[Column(unique: true)]
    private string $chatId;

    #[Column]
    private string $name;

    #[OneToOne(targetEntity: ChatConfig::class, cascade: ["persist", "remove"])]
    private ChatConfig $config;

    #[OneToMany(mappedBy: "chat", targetEntity: Message::class)]
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

}