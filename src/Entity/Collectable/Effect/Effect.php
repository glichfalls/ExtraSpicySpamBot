<?php

namespace App\Entity\Collectable\Effect;

use App\Entity\Collectable\Collectable;
use App\Model\Id;
use App\Repository\EffectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity(repositoryClass: EffectRepository::class)]
class Effect
{
    use Id;

    #[Column(type: 'string')]
    private string $type;

    #[Column(type: 'float')]
    private float $magnitude;

    #[Column(type: 'string')]
    private string $operator;

    #[Column(type: 'string')]
    private string $name;

    #[Column(type: 'text')]
    private string $description;

    #[ManyToMany(targetEntity: Collectable::class, inversedBy: 'effects')]
    private Collection $collectables;

    public function __construct()
    {
        $this->generateId();
        $this->collectables = new ArrayCollection();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getMagnitude(): float
    {
        return $this->magnitude;
    }

    public function setMagnitude(float $magnitude): void
    {
        $this->magnitude = $magnitude;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
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

    public function apply(int|float $value): int|float
    {
        return match ($this->operator) {
            '+' => $value + $this->magnitude,
            '-' => $value - $this->magnitude,
            '*' => $value * $this->magnitude,
            '/' => $value / $this->magnitude,
            default => throw new \RuntimeException(sprintf('Unknown operator "%s"', $this->operator)),
        };
    }

}