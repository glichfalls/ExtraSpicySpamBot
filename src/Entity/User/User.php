<?php

namespace App\Entity\User;

use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Model\Id;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: UserRepository::class)]
class User
{
    use Id;
    use TimestampableEntity;

    #[Column(type: 'integer')]
    private int $telegramUserId;

    #[Column(type: 'string', nullable: true)]
    private ?string $name = null;

    #[Column(type: 'string', nullable: true)]
    private ?string $firstName = null;

    #[Column(type: 'string', nullable: true)]
    private ?string $lastName = null;

    #[OneToMany(mappedBy: 'sender', targetEntity: Honor::class)]
    private Collection $sentHonor;

    #[OneToMany(mappedBy: 'recipient', targetEntity: Honor::class)]
    private Collection $receivedHonor;

    #[OneToMany(mappedBy: 'user', targetEntity: Message::class)]
    private Collection $messages;

    public function __construct()
    {
        $this->generateId();
        $this->sentHonor = new ArrayCollection();
        $this->receivedHonor = new ArrayCollection();
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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

}