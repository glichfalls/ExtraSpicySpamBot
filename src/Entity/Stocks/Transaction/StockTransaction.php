<?php

namespace App\Entity\Stocks\Transaction;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Entity\Stocks\Stock\StockPrice;
use App\Model\Id;
use App\Repository\Stocks\StockTransactionRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity(repositoryClass: StockTransactionRepository::class)]
#[ApiResource(
    description: 'Stock transaction',
    normalizationContext: ['groups' => ['stock:read', 'portfolio:read']],
    paginationEnabled: false,
)]
class StockTransaction
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Portfolio::class, inversedBy: 'transactions')]
    #[JoinColumn(nullable: false)]
    private Portfolio $portfolio;

    #[ManyToOne(targetEntity: StockPrice::class)]
    #[JoinColumn(nullable: false)]
    #[Groups(['stock:read', 'portfolio:read'])]
    private StockPrice $price;

    #[Column(type: 'integer', nullable: false)]
    #[Groups(['stock:read', 'portfolio:read'])]
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

    #[ApiProperty(
        description: 'The total amount of honor spent on this transaction',
        readable: true
    )]
    #[Groups(['stock:read'])]
    public function getHonorTotal(): int
    {
        if ($this->honorTotal === null) {
            $this->honorTotal = $this->getPrice()->getHonorPrice() * $this->getAmount();
        }
        return $this->honorTotal;
    }

    #[Groups(['stock:read', 'portfolio:read'])]
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

}