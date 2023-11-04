<?php

namespace App\Entity\Item\Effect;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Item\Item;
use App\Model\Id;
use App\Repository\EffectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity(repositoryClass: EffectRepository::class)]
#[ApiResource(
    normalizationContext: [
        'groups' => [
            'public:read',
            'effect:read',
        ],
    ],
)]
class Effect
{
    use Id;

    #[Column(type: 'string')]
    #[Groups(['effect:read', 'collectable:read'])]
    private string $type;

    #[Column(type: 'float')]
    #[Groups(['effect:read', 'collectable:read'])]
    private float $magnitude;

    #[Column(type: 'string')]
    #[Groups(['effect:read', 'collectable:read'])]
    private string $operator;

    #[Column(type: 'integer')]
    #[Groups(['effect:read', 'collectable:read'])]
    private int $priority = 100;

    #[Column(type: 'string')]
    #[Groups(['effect:read', 'collectable:read'])]
    private string $name;

    #[Column(type: 'text')]
    #[Groups(['effect:read', 'collectable:read'])]
    private string $description;

    #[ManyToMany(targetEntity: Item::class, inversedBy: 'effects')]
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

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
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

    public function getCollectables(): Collection
    {
        return $this->collectables;
    }

    public function addCollectable(Item $collectable): void
    {
        if (!$this->collectables->contains($collectable)) {
            $this->collectables->add($collectable);
        }
    }

    public function removeCollectable(Item $collectable): void
    {
        if ($this->collectables->contains($collectable)) {
            $this->collectables->removeElement($collectable);
        }
    }

    public function apply(int|float $value): int|float
    {
        return match ($this->operator) {
            '+' => $value + $this->magnitude,
            '-' => $value - $this->magnitude,
            '*' => $value * $this->magnitude,
            '/' => $value / $this->magnitude,
            '=' => $this->magnitude,
            default => throw new \RuntimeException(sprintf('Unknown operator "%s"', $this->operator)),
        };
    }

}