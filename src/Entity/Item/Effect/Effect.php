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
use Doctrine\ORM\Mapping\OneToMany;
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
class Effect implements EffectApplicable
{
    use Id;

    #[Column(type: 'string', enumType: EffectType::class)]
    #[Groups(['effect:read', 'collectable:read'])]
    private EffectType $type;

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

    #[OneToMany(targetEntity: ItemEffect::class, mappedBy: 'effect')]
    private Collection $items;

    public function __construct()
    {
        $this->generateId();
        $this->items = new ArrayCollection();
    }

    public function getType(): EffectType
    {
        return $this->type;
    }

    public function setType(EffectType $type): void
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

    /**
     * @return Collection<Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }
    }

    public function removeItem(Item $item): void
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
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

    public function applyNegative(int|float $value): int|float
    {
        return match ($this->operator) {
            '+' => $value - $this->magnitude,
            '-' => $value + $this->magnitude,
            '*' => $value / $this->magnitude,
            '/' => $value * $this->magnitude,
            '=' => $this->magnitude,
            default => throw new \RuntimeException(sprintf('Unknown operator "%s"', $this->operator)),
        };
    }

}