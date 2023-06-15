<?php

namespace App\Entity\Honor\Raid;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\RaidRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: RaidRepository::class)]
class Raid
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[ManyToOne(targetEntity: User::class)]
    private User $target;

    #[ManyToOne(targetEntity: User::class)]
    private User $leader;

    #[ManyToMany(targetEntity: User::class)]
    #[JoinTable(name: 'raid_supporters')]
    private Collection $supporters;

    #[ManyToMany(targetEntity: User::class)]
    #[JoinTable(name: 'raid_defenders')]
    private Collection $defenders;

    #[Column(type: 'boolean')]
    private bool $isActive = true;

    #[Column(type: 'boolean', nullable: true)]
    private ?bool $isSuccessful = null;

    public function __construct()
    {
        $this->generateId();
        $this->supporters = new ArrayCollection();
        $this->defenders = new ArrayCollection();
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
    }

    public function getLeader(): User
    {
        return $this->leader;
    }

    public function setLeader(User $leader): void
    {
        $this->leader = $leader;
    }

    public function getTarget(): User
    {
        return $this->target;
    }

    public function setTarget(User $target): void
    {
        $this->target = $target;
    }

    /**
     * @return Collection<User>
     */
    public function getSupporters(): Collection
    {
        return $this->supporters;
    }

    public function setSupporters(Collection $supporters): void
    {
        $this->supporters = $supporters;
    }

    /**
     * @return Collection<User>
     */
    public function getDefenders(): Collection
    {
        return $this->defenders;
    }

    public function setDefenders(Collection $defenders): void
    {
        $this->defenders = $defenders;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isSuccessful(): ?bool
    {
        return $this->isSuccessful;
    }

    public function setIsSuccessful(?bool $isSuccessful): void
    {
        $this->isSuccessful = $isSuccessful;
    }

}