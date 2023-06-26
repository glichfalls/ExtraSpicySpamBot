<?php

namespace App\Entity\Honor\HonorMillions\Draw;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorMillions\Ticket\Ticket;
use App\Model\Id;
use App\Repository\DrawRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity(repositoryClass: DrawRepository::class)]
class Draw
{
    use Id;

    #[Column(type: 'integer')]
    private int $previousJackpot;

    #[Column(type: 'integer')]
    private int $gamblingLosses = 0;

    #[Column(type: 'date')]
    private \DateTime $date;

    #[Column(type: 'integer', nullable: true)]
    private ?int $winningNumber = null;

    #[ManyToOne(targetEntity: Chat::class)]
    private Chat $chat;

    #[Column(type: 'integer', nullable: true)]
    private ?int $telegramThreadId = null;

    #[OneToOne(targetEntity: Draw::class)]
    private ?Draw $previousDraw = null;

    #[OneToMany(mappedBy: 'draw', targetEntity: Ticket::class)]
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

    public function getGamblingLosses(): int
    {
        return $this->gamblingLosses;
    }

    public function setGamblingLosses(int $gamblingLosses): void
    {
        $this->gamblingLosses = $gamblingLosses;
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

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
    }

    public function getTelegramThreadId(): ?int
    {
        return $this->telegramThreadId;
    }

    public function setTelegramThreadId(?int $telegramThreadId): void
    {
        $this->telegramThreadId = $telegramThreadId;
    }

    public function getPreviousDraw(): ?Draw
    {
        return $this->previousDraw;
    }

    public function setPreviousDraw(?Draw $previousDraw): void
    {
        $this->previousDraw = $previousDraw;
    }

    /**
     * @return Collection<Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): void
    {
        $this->tickets->add($ticket);
    }

    public function getJackpot(): int
    {
        $jackpot = $this->getPreviousJackpot() + $this->getGamblingLosses();
        $jackpot += $this->getTickets()->count() * Ticket::TICKET_PRICE;
        return $jackpot;
    }

}