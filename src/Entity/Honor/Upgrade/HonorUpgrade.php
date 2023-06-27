<?php

namespace App\Entity\Honor\Upgrade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\HonorUpgradeRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: HonorUpgradeRepository::class)]
class HonorUpgrade
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: User::class)]
    private Chat $chat;

    #[ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ManyToOne(targetEntity: UpgradeType::class)]
    private UpgradeType $type;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getType(): UpgradeType
    {
        return $this->type;
    }

    public function setType(UpgradeType $type): void
    {
        $this->type = $type;
    }

}