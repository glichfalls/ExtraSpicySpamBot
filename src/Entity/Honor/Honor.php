<?php

namespace App\Entity\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Season\Season;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\HonorRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Currency;
use Money\Money;

#[Entity(repositoryClass: HonorRepository::class)]
class Honor
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'sentHonor')]
    private ?User $sender = null;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'receivedHonor')]
    private User $recipient;

    #[ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[ManyToOne(targetEntity: Season::class)]
    private Season $season;

    #[Column(type: 'honor', nullable: false)]
    private Money $amount;

    public static function currency(int|string $amount): Money
    {
        return new Money($amount, new Currency('Ehre'));
    }

    public function __construct()
    {
        $this->generateId();
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): void
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

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function setSeason(Season $season): void
    {
        $this->season = $season;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount;
    }

}