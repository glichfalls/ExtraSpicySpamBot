<?php

namespace App\Entity\Honor\HonorMillions\Ticket;

use App\Entity\Honor\HonorMillions\Draw\Draw;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\TicketRepository;
use App\Types\HonorType;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Money\Money;

#[Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    use Id;

    #[ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ManyToOne(targetEntity: Draw::class, inversedBy: 'tickets')]
    private Draw $draw;

    #[Column(type: 'json')]
    private array $numbers = [];

    public function __construct()
    {
        $this->generateId();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setDraw(Draw $draw): void
    {
        $this->draw = $draw;
    }

    public function getDraw(): Draw
    {
        return $this->draw;
    }

    public function addNumber(int $number): void
    {
        $this->numbers[] = $number;
    }

    public function getNumbers(): array
    {
        return $this->numbers;
    }

    public function getTotalCost(): Money
    {
        $ticketCount = count($this->numbers);
        if ($ticketCount <= 1) {
            return HonorType::create(0);
        }
        $total = 0;
        for ($i = 1; $i < $ticketCount; $i++) {
            $total += pow(10, $i + 1);
        }
        return HonorType::create($total);
    }

}