<?php

namespace App\Entity\Chat;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
class ChatConfig
{
    use Id;

    #[OneToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[Column(type: "boolean")]
    #[Groups(['chat:public:read'])]
    private bool $passiveHonorEnabled = true;

    #[Column(type: "integer")]
    #[Groups(['chat:public:read'])]
    private int $passiveHonorAmount = 100;

    #[Column(type: "string")]
    private string $timezone = 'UTC';

    #[Column(type: "boolean")]
    private bool $debugEnabled = false;

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

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function isDebugEnabled(): bool
    {
        return $this->debugEnabled;
    }

    public function setDebugEnabled(bool $debugEnabled): void
    {
        $this->debugEnabled = $debugEnabled;
    }

}