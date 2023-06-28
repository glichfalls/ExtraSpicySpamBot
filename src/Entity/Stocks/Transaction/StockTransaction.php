<?php

namespace App\Entity\Stocks\Transaction;

use App\Entity\Stocks\Portfolio\Portfolio;
use App\Entity\Stocks\Stock\Stock;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class StockTransaction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Portfolio::class, inversedBy: 'transactions')]
    #[JoinColumn(nullable: false)]
    private Portfolio $portfolio;

    #[ManyToOne(targetEntity: Stock::class)]
    private Stock $stock;

    #[Column(type: 'integer', nullable: false)]
    private int $amount;

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $price;

    public function __construct()
    {
        $this->generateId();
    }

    public function getPortfolio(): Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function setStock(Stock $stock): void
    {
        $this->stock = $stock;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

}