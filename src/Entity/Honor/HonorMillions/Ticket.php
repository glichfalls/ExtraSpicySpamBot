<?php

namespace App\Entity\Honor\HonorMillions;

use App\Entity\User\User;
use App\Model\Id;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Ticket
{
    use Id;

    private User $user;

    private Draw $draw;

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