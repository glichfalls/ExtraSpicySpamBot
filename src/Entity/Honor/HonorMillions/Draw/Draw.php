<?php declare(strict_types=1);

namespace App\Entity\Honor\HonorMillions\Draw;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\HonorMillions\Ticket\Ticket;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\DrawRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Money\Money;

#[Entity(repositoryClass: DrawRepository::class)]
class Draw
{
    use Id;

    #[Column(type: 'honor', nullable: true)]
    private ?Money $previousJackpot = null;

    #[Column(type: 'honor', nullable: true)]
    private ?Money $gamblingLosses = null;

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

    public function getPreviousJackpot(): Money
    {
        return $this->previousJackpot;
    }

    public function setPreviousJackpot(Money $previousJackpot): void
    {
        $this->previousJackpot = $previousJackpot;
    }

    public function getGamblingLosses(): Money
    {
        return $this->gamblingLosses;
    }

    public function setGamblingLosses(Money $gamblingLosses): void
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

    public function getWinningNumber(): ?int
    {
        return $this->winningNumber;
    }

    public function setWinningNumber(?int $winningNumber): void
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

    public function getTicketByUser(User $user): ?Ticket
    {
        return $this->getTickets()->filter(fn (Ticket $ticket) => $ticket->getUser() === $user)->first() ?: null;
    }

    public function addTicket(Ticket $ticket): void
    {
        $this->tickets->add($ticket);
    }

    public function getJackpot(): Money
    {
        $ticketPriceSum = $this->getTickets()->reduce(fn (Money $sum, Ticket $ticket) => $sum->add($ticket->getTotalCost()), Honor::currency(0));
        return $this->getPreviousJackpot()->add($ticketPriceSum)->add($this->getGamblingLosses());
    }

    /**
     * @return Collection<Ticket>
     */
    public function getWinners(): Collection
    {
        if ($this->getWinningNumber() === null) {
            return new ArrayCollection();
        }
        return $this->getTickets()->filter(fn (Ticket $ticket) => in_array($this->getWinningNumber(), $ticket->getNumbers()));
    }

}
