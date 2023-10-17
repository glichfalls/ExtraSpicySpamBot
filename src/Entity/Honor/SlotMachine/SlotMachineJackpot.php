<?php

namespace App\Entity\Honor\SlotMachine;

use App\Entity\Chat\Chat;
use App\Model\Id;
use App\Repository\SlotMachineJackpotRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity(repositoryClass: SlotMachineJackpotRepository::class)]
class SlotMachineJackpot
{
    use Id;

    #[ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[Column(type: 'bigint')]
    private int $amount;

    #[Column(type: 'boolean')]
    private bool $active;

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

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

}