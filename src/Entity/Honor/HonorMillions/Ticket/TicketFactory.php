<?php

namespace App\Entity\Honor\HonorMillions\Ticket;

use App\Entity\Honor\HonorMillions\Draw\Draw;
use App\Entity\User\User;

class TicketFactory
{

    public static function create(User $user, Draw $draw): Ticket
    {
        $ticket = new Ticket();
        $ticket->setUser($user);
        $ticket->setDraw($draw);
        return $ticket;
    }

}