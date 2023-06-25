<?php

namespace App\Entity\Honor\HonorMillions\Ticket;

use App\Entity\Honor\HonorMillions\Draw\Draw;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    use Id;

    public const TICKET_PRICE = 100;

    #[ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ManyToOne(targetEntity: Draw::class, inversedBy: 'tickets')]
    private Draw $draw;

    #[Column(type: 'integer')]
    private int $number;

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

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

}