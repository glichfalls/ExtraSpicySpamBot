<?php

namespace App\Entity\WarGame;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

class ArmyUnit
{
    use Id;

    private Army $army;

    private Unit $unit;

    #[Column(type: 'integer')]
    private int $quantity;

    public function __construct(Army $army, Unit $unit, int $quantity)
    {
        $this->army = $army;
        $this->unit = $unit;
        $this->quantity = $quantity;
    }

    public function getArmy(): Army
    {
        return $this->army;
    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
