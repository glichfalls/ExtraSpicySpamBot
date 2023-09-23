<?php

namespace App\Entity\WarGame;

use App\Entity\User\User;
use App\Model\Id;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;


class Army
{
    use Id;

    private User $owner;

    #[ManyToMany(targetEntity: Unit::class)]
    #[JoinTable(name: 'army_units')]
    private Unit $units;

}