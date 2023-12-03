<?php

namespace App\Entity\Item\Effect;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Item\Item;
use App\Model\Id;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[ApiResource(
    normalizationContext: ['groups' => [
        'public:read',
        'collectable:read',
        'item:effect:read',
    ]],
    denormalizationContext: ['groups' => [
        'item:effect:write',
    ]],
)]
class ItemEffect implements EffectApplicable
{
    use Id;

    #[ManyToOne(targetEntity: Effect::class, inversedBy: 'items')]
    #[JoinColumn(nullable: false)]
    #[Groups(['collectable:read', 'item:effect:write'])]
    private Effect $effect;

    #[ManyToOne(targetEntity: Item::class, inversedBy: 'effects')]
    #[JoinColumn(nullable: false)]
    #[Groups(['collectable:read', 'item:effect:write'])]
    private Item $item;

    public function __construct()
    {
        $this->generateId();
    }

    public function getEffect(): Effect
    {
        return $this->effect;
    }

    public function setEffect(Effect $effect): void
    {
        $this->effect = $effect;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    public function getType(): EffectType
    {
        return $this->effect->getType();
    }

    public function getName(): string
    {
        return $this->effect->getName();
    }

    public function getDescription(): string
    {
        return $this->effect->getDescription();
    }

    public function getOperator(): string
    {
        return $this->effect->getOperator();
    }

    public function getMagnitude(): string
    {
        return $this->effect->getMagnitude();
    }

    public function apply(string $value): string
    {
        return $this->effect->apply($value);
    }

    public function applyNegative(string $value): string
    {
        return $this->effect->applyNegative($value);
    }
}