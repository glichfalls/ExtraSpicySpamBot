<?php

namespace App\Entity\Honor;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class Honor
{
    use Id;
    use TimestampableEntity;

    #[Column]
    #[ManyToOne(targetEntity: User::class)]
    private User $sender;

    #[Column]
    #[ManyToOne(targetEntity: User::class, inversedBy: 'honor')]
    private User $recipient;

    #[Column]
    #[ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[Column(type: 'integer')]
    private int $amount = 0;

    public function __construct()
    {
        $this->generateId();
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): void
    {
        $this->sender = $sender;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

}