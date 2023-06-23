<?php

namespace App\Entity\Sticker;

use App\Entity\User\User;
use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class StickerFile
{
    use Id;

    #[ManyToOne(targetEntity: User::class)]
    private User $owner;

    #[Column(type: 'text', nullable: false)]
    private string $sticker;

    #[Column(type: 'string', nullable: false)]
    private string $stickerFormat;

    #[Column(type: 'string', nullable: true)]
    private ?string $fileId = null;

    #[Column(type: 'string', nullable: true)]
    private ?string $fileUniqueId = null;

    #[Column(type: 'bigint', nullable: true)]
    private ?string $fileSize = null;

    #[Column(type: 'string', nullable: true)]
    private ?string $filePath = null;

    public function __construct()
    {
        $this->generateId();
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function getSticker(): string
    {
        return $this->sticker;
    }

    public function setSticker(string $sticker): void
    {
        $this->sticker = $sticker;
    }

    public function getStickerFormat(): string
    {
        return $this->stickerFormat;
    }

    public function setStickerFormat(string $stickerFormat): void
    {
        $this->stickerFormat = $stickerFormat;
    }

    public function getFileId(): ?string
    {
        return $this->fileId;
    }

    public function setFileId(?string $fileId): void
    {
        $this->fileId = $fileId;
    }

    public function getFileUniqueId(): ?string
    {
        return $this->fileUniqueId;
    }

    public function setFileUniqueId(?string $fileUniqueId): void
    {
        $this->fileUniqueId = $fileUniqueId;
    }

    public function getFileSize(): ?string
    {
        return $this->fileSize;
    }

    public function setFileSize(?string $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

}