<?php

namespace App\Entity\Honor\Bank;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class Transaction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: BankAccount::class)]
    private BankAccount $bankAccount;

    #[Column(type: 'bigint', nullable: false)]
    private int $amount = 0;

    public function __construct()
    {
        $this->generateId();
    }

    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(BankAccount $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

}