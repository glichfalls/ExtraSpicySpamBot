<?php

namespace App\Entity\OneToHowMuch;

use App\Entity\User\User;
use App\Model\Id;
use App\Repository\OneToHowMuchRoundRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity(repositoryClass: OneToHowMuchRoundRepository::class)]
class OneToHowMuchRound
{
    use Id;
    use TimestampableEntity;

    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(nullable: false)]
    private User $challenger;

    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(nullable: false)]
    private User $opponent;

    #[ManyToOne(targetEntity: User::class)]
    private ?User $winner = null;

    #[Column(type: 'integer', nullable: true)]
    private ?int $challengerNumber = null;

    #[Column(type: 'integer', nullable: true)]
    private ?int $opponentNumber = null;

    #[Column(type: 'boolean')]
    private bool $accepted = false;

    #[Column(name: 'number_range', type: 'integer')]
    private int $range = 100;

    public function __construct()
    {
        $this->generateId();
    }

    public function getChallenger(): User
    {
        return $this->challenger;
    }

    public function setChallenger(User $challenger): void
    {
        $this->challenger = $challenger;
    }

    public function getOpponent(): User
    {
        return $this->opponent;
    }

    public function setOpponent(User $opponent): void
    {
        $this->opponent = $opponent;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(User $winner): void
    {
        $this->winner = $winner;
    }

    public function getChallengerNumber(): ?int
    {
        return $this->challengerNumber;
    }

    public function setChallengerNumber(?int $challengerNumber): void
    {
        $this->challengerNumber = $challengerNumber;
    }

    public function getOpponentNumber(): ?int
    {
        return $this->opponentNumber;
    }

    public function setOpponentNumber(?int $opponentNumber): void
    {
        $this->opponentNumber = $opponentNumber;
    }

    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): void
    {
        $this->accepted = $accepted;
    }

    public function getRange(): int
    {
        return $this->range;
    }

    public function setRange(int $range): void
    {
        $this->range = $range;
    }

}