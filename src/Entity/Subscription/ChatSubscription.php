<?php

namespace App\Entity\Subscription;

use App\Entity\Chat\Chat;
use App\Model\Id;
use App\Repository\ChatSubscriptionRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: ChatSubscriptionRepository::class)]
class ChatSubscription
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[Column]
    private string $type;

    public function __construct()
    {
        $this->generateId();
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
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