<?php

namespace App\Entity\User;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class User
{
    use Id;

    #[Column(type: 'integer')]
    private int $telegramUserId;

    #[Column(type: 'string', nullable: true)]
    private ?string $name = null;

    #[Column(type: 'string', nullable: true)]
    private ?string $firstName = null;

    #[Column(type: 'integer')]
    private int $honor = 0;

    public function __construct()
    {
        $this->generateId();
    }

    public function getTelegramUserId(): int
    {
        return $this->telegramUserId;
    }

    public function setTelegramUserId(int $telegramUserId): void
    {
        $this->telegramUserId = $telegramUserId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getHonor(): int
    {
        return $this->honor;
    }

    public function setHonor(int $honor): void
    {
        $this->honor = $honor;
    }

}