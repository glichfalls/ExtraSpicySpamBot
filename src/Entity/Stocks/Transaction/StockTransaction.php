<?php declare(strict_types=1);

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
use Money\Money;
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

    #[Column(type: 'string', nullable: false)]
    #[Groups(['stock:read', 'portfolio:read'])]
    private string $amount;

    private ?float $total = null;

    private ?Money $honorTotal = null;

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

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
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
    public function getHonorTotal(): Money
    {
        if ($this->honorTotal === null) {
            $this->honorTotal = $this->getPrice()->getHonorPrice()->multiply($this->getAmount());
        }
        return $this->honorTotal;
    }

    #[Groups(['stock:read', 'portfolio:read'])]
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

}