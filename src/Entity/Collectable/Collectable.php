<?php

namespace App\Entity\Collectable;

use App\Entity\Collectable\Effect\Effect;
use App\Model\Id;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class Collectable
{
    use Id;

    #[Column(type: 'string')]
    private string $name;

    #[Column(type: 'text')]
    private string $description;

    #[Column(type: 'boolean')]
    private bool $tradeable;

    #[Column(type: 'boolean')]
    private bool $isUnique;

    #[Column(type: 'text', nullable: true)]
    private ?string $imagePublicPath = null;

    #[ManyToMany(targetEntity: Effect::class, mappedBy: 'collectables')]
    private Collection $effects;

    #[OneToMany(mappedBy: 'collectable', targetEntity: CollectableItemInstance::class)]
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

    public function isTradeable(): bool
    {
        return $this->tradeable;
    }

    public function setTradeable(bool $tradeable): void
    {
        $this->tradeable = $tradeable;
    }

    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    public function setUnique(bool $unique): void
    {
        $this->isUnique = $unique;
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

    public function setEffects(Collection $effects): void
    {
        $this->effects = $effects;
    }

    public function getInstances(): Collection
    {
        return $this->instances;
    }

    public function setInstances(Collection $instances): void
    {
        $this->instances = $instances;
    }

    public function isInstancable(): bool
    {
        if (!$this->isUnique()) {
            return true;
        }
        return $this->getInstances()->count() === 0;
    }

}