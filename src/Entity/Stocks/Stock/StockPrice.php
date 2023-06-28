<?php

namespace App\Entity\Stocks\Stock;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class StockPrice
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: Stock::class, inversedBy: 'stockPrices')]
    #[JoinColumn(nullable: false)]
    private Stock $stock;

    #[Column(type: 'float', nullable: false)]
    private float $price;

    #[Column(type: 'float', nullable: true)]
    private ?float $changeAbsolute = null;

    #[Column(type: 'float', nullable: true)]
    private ?string $changePercent = null;

    public function __construct()
    {
        $this->generateId();
    }

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function setStock(Stock $stock): void
    {
        $this->stock = $stock;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getChangeAbsolute(): ?float
    {
        return $this->changeAbsolute;
    }

    public function setChangeAbsolute(?float $changeAbsolute): void
    {
        $this->changeAbsolute = $changeAbsolute;
    }

    public function getChangePercent(): ?float
    {
        return $this->changePercent;
    }

    public function setChangePercent(?float $changePercent): void
    {
        $this->changePercent = $changePercent;
    }

    public function getHonorPrice(): int
    {
        return (int) round($this->getPrice());
    }

}