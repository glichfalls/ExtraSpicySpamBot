<?php

namespace App\Entity\Chat;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Chat
{
    use Id;

    #[Column(unique: true)]
    private string $chatId;

    #[Column]
    private string $name;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

}