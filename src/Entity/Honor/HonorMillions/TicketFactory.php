<?php

namespace App\Entity\Honor\HonorMillions;

use App\Entity\User\User;

class TicketFactory
{

    public static function create(User $user, Draw $draw, int $number): Ticket
    {
        $ticket = new Ticket();
        $ticket->setUser($user);
        $ticket->setDraw($draw);
        $ticket->setNumber($number);
        return $ticket;
    }

}