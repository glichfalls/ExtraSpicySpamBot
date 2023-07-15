<?php

namespace App\Entity\Chat;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class ChatConfig
{
    use Id;

    #[OneToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[Column(type: "boolean")]
    private bool $passiveHonorEnabled = true;

    #[Column(type: "integer")]
    private int $passiveHonorAmount = 100;

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

    public function isPassiveHonorEnabled(): bool
    {
        return $this->passiveHonorEnabled;
    }

    public function setPassiveHonorEnabled(bool $passiveHonorEnabled): void
    {
        $this->passiveHonorEnabled = $passiveHonorEnabled;
    }

    public function getPassiveHonorAmount(): int
    {
        return $this->passiveHonorAmount;
    }

    public function setPassiveHonorAmount(int $passiveHonorAmount): void
    {
        $this->passiveHonorAmount = $passiveHonorAmount;
    }

}