<?php

namespace App\Entity\Message;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: MessageRepository::class)]
#[ApiResource(order: ['createdAt' => 'DESC'])]
#[ApiFilter(SearchFilter::class, properties: [
    'chat' => 'exact',
    'chat.id' => 'exact',
    'user' => 'exact',
    'user.id' => 'exact',
    'telegramThreadId' => 'exact',
    'message' => 'partial',
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
class Message
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Chat::class, inversedBy: 'messages')]
    private Chat $chat;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    private User $user;

    #[Column(type: 'integer', nullable: true)]
    private ?int $telegramMessageId = null;

    #[Column(type: 'integer', nullable: true)]
    private ?int $telegramThreadId = null;

    #[Column(type: 'text')]
    private string $message;

    public function __construct()
    {
        $this->generateId();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
    }

    public function getTelegramMessageId(): ?int
    {
        return $this->telegramMessageId;
    }

    public function setTelegramMessageId(?int $telegramMessageId): void
    {
        $this->telegramMessageId = $telegramMessageId;
    }

    public function getTelegramThreadId(): ?int
    {
        return $this->telegramThreadId;
    }

    public function setTelegramThreadId(?int $telegramThreadId): void
    {
        $this->telegramThreadId = $telegramThreadId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

}