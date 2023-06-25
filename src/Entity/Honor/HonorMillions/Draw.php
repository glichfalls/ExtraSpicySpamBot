<?php

namespace App\Entity\Honor\HonorMillions;

use App\Model\Id;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class Draw
{
    use Id;

    #[Column(type: 'integer')]
    private int $previousJackpot;

    #[Column(type: 'date')]
    private \DateTime $date;

    #[Column(type: 'integer', nullable: true)]
    private ?int $winningNumber = null;

    #[OneToOne(targetEntity: Draw::class)]
    private ?Draw $previousDraw = null;

    private Collection $tickets;

    public function __construct()
    {
        $this->generateId();
        $this->tickets = new ArrayCollection();
    }

    public function getPreviousJackpot(): int
    {
        return $this->previousJackpot;
    }

    public function setPreviousJackpot(int $previousJackpot): void
    {
        $this->previousJackpot = $previousJackpot;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getWinningNumber(): int
    {
        return $this->winningNumber;
    }

    public function setWinningNumber(int $winningNumber): void
    {
        $this->winningNumber = $winningNumber;
    }

}