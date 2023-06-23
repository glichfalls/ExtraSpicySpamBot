<?php

namespace App\Entity\Sticker;

use App\Entity\User\User;
use App\Model\Id;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class StickerSet
{
    use Id;

    #[Column(type: 'string', length: 64, unique: true)]
    private string $name;

    #[Column(type: 'string', length: 64)]
    private string $title;

    #[Column(type: 'string')]
    private string $stickerFormat;

    #[ManyToOne(targetEntity: User::class)]
    private User $owner;

    #[OneToMany(mappedBy: 'stickerSet', targetEntity: Sticker::class)]
    private Collection $stickers;

    public function __construct()
    {
        $this->generateId();
        $this->stickers = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStickerFormat(): string
    {
        return $this->stickerFormat;
    }

    public function setStickerFormat(string $stickerFormat): void
    {
        $this->stickerFormat = $stickerFormat;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function getStickers(): Collection
    {
        return $this->stickers;
    }

    public function setStickers(Collection $stickers): void
    {
        $this->stickers = $stickers;
    }

}