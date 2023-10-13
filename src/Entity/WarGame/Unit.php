<?php

namespace App\Entity\WarGame;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
class Unit
{
    use Id;

    #[Column(type: 'string')]
    #[Groups(['public:read'])]
    private string $name;

    #[Column(type: 'text')]
    #[Groups(['public:read'])]
    private string $description;

    #[Column]
    #[Groups(['public:read'])]
    private int $attack;

    #[Column]
    #[Groups(['public:read'])]
    private int $defense;

    public function __construct()
    {
        $this->generateId();
    }

    public function getName(): string
    {
        return $this->name;
    }

}