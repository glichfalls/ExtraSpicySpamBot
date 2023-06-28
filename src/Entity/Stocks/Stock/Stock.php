<?php

namespace App\Entity\Stocks\Stock;

use App\Model\Id;
use App\Repository\Stocks\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

#[Entity(repositoryClass: StockRepository::class)]
class Stock
{
    use Id;

    #[Column(type: 'string', nullable: false)]
    private string $name;

    #[Column(type: 'string', nullable: false)]
    private string $displaySymbol;

    #[Column(type: 'string', unique: true, nullable: false)]
    private string $symbol;

    #[Column(type: 'string', nullable: false)]
    private string $type;

    #[OneToMany(mappedBy: 'stock', targetEntity: StockPrice::class, cascade: ['persist'])]
    #[OrderBy(['date' => 'DESC'])]
    private Collection $stockPrices;

    public function __construct()
    {
        $this->generateId();
        $this->stockPrices = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDisplaySymbol(): string
    {
        return $this->displaySymbol;
    }

    public function setDisplaySymbol(string $displaySymbol): void
    {
        $this->displaySymbol = $displaySymbol;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getStockPrices(): Collection
    {
        return $this->stockPrices;
    }

    public function addStockPrice(StockPrice $stockPrice): void
    {
        $this->stockPrices->add($stockPrice);
    }

    public function getLatestStockPrice(): ?StockPrice
    {
        return $this->stockPrices->first() ?: null;
    }

}