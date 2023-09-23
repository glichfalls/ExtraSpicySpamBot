<?php

namespace App\Entity\Collectable;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class CollectableItemInstance
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Chat::class)]
    #[JoinColumn(nullable: false)]
    private Chat $chat;

    #[ManyToOne(targetEntity: Collectable::class)]
    #[JoinColumn(nullable: false)]
    private Collectable $collectable;

    #[OneToMany(mappedBy: 'instance', targetEntity: CollectableTransaction::class)]
    #[OrderBy(['createdAt' => 'DESC'])]
    protected Collection $transactions;

    public function __construct()
    {
        $this->generateId();
        $this->transactions = new ArrayCollection();
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
    }

    public function getCollectable(): Collectable
    {
        return $this->collectable;
    }

    public function setCollectable(Collectable $collectable): void
    {
        $this->collectable = $collectable;
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function setTransactions(Collection $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function getCurrentTransaction(): ?CollectableTransaction
    {
        return $this->getTransactions()
            ->filter(fn (CollectableTransaction $transaction) => $transaction->isCompleted())
            ->last();
    }

    public function getOwner(): ?User
    {
        return $this->getCurrentTransaction()?->getBuyer();
    }

}
