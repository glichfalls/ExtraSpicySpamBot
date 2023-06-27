<?php

namespace App\Entity\Honor\Bank;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\BankAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

#[Entity(repositoryClass: BankAccountRepository::class)]
class BankAccount
{
    use Id;

    #[ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[ManyToOne(targetEntity: User::class)]
    private User $user;

    #[OneToMany(mappedBy: 'bankAccount', targetEntity: Transaction::class, cascade: ['persist'])]
    #[OrderBy(['createdAt' => 'DESC'])]
    private Collection $transactions;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): void
    {
        $transaction->setBankAccount($this);
        $this->transactions->add($transaction);
    }

    public function getBalance(): int
    {
        return array_reduce(
            $this->transactions->toArray(),
            fn (int $balance, Transaction $transaction) => $balance + $transaction->getAmount(),
            0
        );
    }

}