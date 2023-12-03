<?php

namespace App\Entity\Honor\Bank;

use App\Entity\Honor\Season\Season;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Money;

#[Entity]
class Transaction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: BankAccount::class)]
    private BankAccount $bankAccount;

    #[ManyToOne(targetEntity: Season::class)]
    private Season $season;

    #[Column(type: 'honor', nullable: false)]
    private Money $amount;

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