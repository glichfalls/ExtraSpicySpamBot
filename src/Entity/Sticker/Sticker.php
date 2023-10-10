<?php

namespace App\Entity\Sticker;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Model\Id;
use App\Repository\StickerFileRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity(repositoryClass: StickerFileRepository::class)]
#[ApiResource()]
class Sticker
{
    use Id;

    #[ManyToOne(targetEntity: StickerSet::class, inversedBy: 'stickers')]
    private StickerSet $stickerSet;

    #[ManyToOne(targetEntity: StickerFile::class)]
    private StickerFile $file;

    #[Column(type: 'json')]
    private array $emojis = [];

    public function __construct()
    {
        $this->generateId();
    }

    public function getStickerSet(): StickerSet
    {
        return $this->stickerSet;
    }

    public function setStickerSet(StickerSet $stickerSet): void
    {
        $this->stickerSet = $stickerSet;
    }

    public function getFile(): StickerFile
    {
        return $this->file;
    }

    public function setFile(StickerFile $file): void
    {
        $this->file = $file;
    }

    public function getEmojis(): array
    {
        return $this->emojis;
    }

    public function setEmojis(array $emojis): void
    {
        $this->emojis = $emojis;
    }

}