<?php

namespace App\Entity\Subscription;

use App\Model\Id;
use App\Repository\ChatSubscriptionRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: ChatSubscriptionRepository::class)]
class ChatSubscription
{
    use Id;
    use TimestampableEntity;

    #[Column]
    private string $chatId;

    #[Column]
    private string $type;

    public function __construct()
    {
        $this->generateId();
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function setChatId(string $chatId): void
    {
        $this->chatId = $chatId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

}