<?php

namespace App\Entity\Stocks\Transaction;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Entity\Stocks\Stock\StockPrice;
use App\Model\Id;
use App\Repository\Stocks\StockTransactionRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: StockTransactionRepository::class)]
#[ApiResource(paginationEnabled: false)]
class StockTransaction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Portfolio::class, inversedBy: 'transactions')]
    #[JoinColumn(nullable: false)]
    private Portfolio $portfolio;

    #[ManyToOne(targetEntity: StockPrice::class)]
    #[JoinColumn(nullable: false)]
    private StockPrice $price;

    #[Column(type: 'integer', nullable: false)]
    private int $amount;

    private ?float $total = null;

    private ?int $honorTotal = null;

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

    public function getPrice(): StockPrice
    {
        return $this->price;
    }

    public function setPrice(StockPrice $price): void
    {
        $this->price = $price;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getTotal(): float
    {
        if ($this->total === null) {
            $this->total = $this->getPrice()->getPrice() * $this->getAmount();
        }
        return $this->total;
    }

    public function getHonorTotal(): int
    {
        if ($this->honorTotal === null) {
            $this->honorTotal = $this->getPrice()->getHonorPrice() * $this->getAmount();
        }
        return $this->honorTotal;
    }

}