<?php

namespace App\Entity\Honor\Stocks\Stock;

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

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $open;

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $close;

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $low;

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $high;

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $preMarket;

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $afterHours;

    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $volume;

    #[Column(type: 'date', nullable: false, options: ['unsigned' => true])]
    private \DateTime $date;

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

    public function getOpen(): int
    {
        return $this->open;
    }

    public function setOpen(int $open): void
    {
        $this->open = $open;
    }

    public function getClose(): int
    {
        return $this->close;
    }

    public function setClose(int $close): void
    {
        $this->close = $close;
    }

    public function getLow(): int
    {
        return $this->low;
    }

    public function setLow(int $low): void
    {
        $this->low = $low;
    }

    public function getHigh(): int
    {
        return $this->high;
    }

    public function setHigh(int $high): void
    {
        $this->high = $high;
    }

    public function getPreMarket(): int
    {
        return $this->preMarket;
    }

    public function setPreMarket(int $preMarket): void
    {
        $this->preMarket = $preMarket;
    }

    public function getAfterHours(): int
    {
        return $this->afterHours;
    }

    public function setAfterHours(int $afterHours): void
    {
        $this->afterHours = $afterHours;
    }

    public function getVolume(): int
    {
        return $this->volume;
    }

    public function setVolume(int $volume): void
    {
        $this->volume = $volume;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

}