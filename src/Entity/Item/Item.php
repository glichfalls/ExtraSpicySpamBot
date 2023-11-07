<?php

namespace App\Entity\Item;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Item\Attribute\ItemAttribute;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\Effect;
use App\Model\Id;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => [
        'public:read',
        'collectable:read'
    ]],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'chat' => 'exact',
    'chat.id' => 'exact',
    'effect' => 'exact',
    'instances' => 'exact',
])]
class Item
{
    use Id;

    #[Column(type: 'string')]
    #[Groups(['collectable:read'])]
    private string $name;

    #[Column(type: 'text')]
    #[Groups(['collectable:read'])]
    private string $description;

    #[Column(type: 'string', enumType: ItemRarity::class)]
    #[Groups(['collectable:read'])]
    private ItemRarity $rarity;

    #[Column(type: 'boolean')]
    #[Groups(['collectable:read'])]
    private bool $permanent;

    #[Column(type: 'json', enumType: ItemAttribute::class)]
    #[Groups(['collectable:read'])]
    private array $attributes = [];

    #[Column(type: 'bigint', nullable: true)]
    #[Groups(['collectable:read'])]
    private ?int $price = null;

    #[Column(type: 'text', nullable: true)]
    #[Groups(['collectable:read'])]
    private ?string $imagePublicPath = null;

    #[ManyToMany(targetEntity: Effect::class, mappedBy: 'collectables', cascade: ['persist', 'remove'])]
    #[Groups(['collectable:read'])]
    private Collection $effects;

    #[OneToMany(mappedBy: 'item', targetEntity: ItemInstance::class)]
    private Collection $instances;

    public function __construct()
    {
        $this->generateId();
        $this->effects = new ArrayCollection();
        $this->instances = new ArrayCollection();
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

    public function getRarity(): ItemRarity
    {
        return $this->rarity;
    }

    public function setRarity(ItemRarity $rarity): void
    {
        $this->rarity = $rarity;
    }

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function setPermanent(bool $permanent): void
    {
        $this->permanent = $permanent;
    }

    /**
     * @return array<ItemAttribute>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(ItemAttribute $attribute): bool
    {
        return in_array($attribute, $this->getAttributes(), true);
    }

    /**
     * @param array<ItemAttribute> $attributes
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function addAttribute(string $key, ItemAttribute $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function removeAttribute(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): void
    {
        $this->price = $price;
    }

    public function getImagePublicPath(): ?string
    {
        return $this->imagePublicPath;
    }

    public function setImagePublicPath(?string $imagePublicPath): void
    {
        $this->imagePublicPath = $imagePublicPath;
    }

    /**
     * @return Collection<Effect>
     */
    public function getEffects(): Collection
    {
        return $this->effects;
    }

    public function addEffect(Effect $effect): void
    {
        if (!$this->effects->contains($effect)) {
            $this->effects->add($effect);
            $effect->addCollectable($this);
        }
    }

    public function removeEffect(Effect $effect): void
    {
        if ($this->effects->contains($effect)) {
            $this->effects->removeElement($effect);
            $effect->removeCollectable($this);
        }
    }

    public function setEffects(Collection $effects): void
    {
        $this->effects = $effects;
    }

    public function getInstances(): Collection
    {
        return $this->instances;
    }

    public function addInstance(ItemInstance $instance): void
    {
        $this->instances->add($instance);
    }

}
