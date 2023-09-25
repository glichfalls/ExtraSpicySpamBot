<?php

namespace App\Entity\Collectable\Effect;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Effect
{
    use Id;

    #[Column(type: 'string')]
    private string $name;

    #[Column(type: 'text')]
    private string $description;

    public function __construct()
    {
        $this->generateId();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

}