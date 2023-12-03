<?php

namespace App\Entity\Chat;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Money\Money;
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

    #[Column(type: "honor")]
    #[Groups(['chat:public:read'])]
    private Money $passiveHonorAmount;

    #[Column(type: "string")]
    private string $timezone = 'UTC';

    #[Column(type: "string", nullable: true)]
    private ?string $defaultThreadId = null;

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

    public function getPassiveHonorAmount(): Money
    {
        return $this->passiveHonorAmount;
    }

    public function setPassiveHonorAmount(Money $passiveHonorAmount): void
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

    public function getDefaultThreadId(): ?string
    {
        return $this->defaultThreadId;
    }

    public function setDefaultThreadId(?string $defaultThreadId): void
    {
        $this->defaultThreadId = $defaultThreadId;
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