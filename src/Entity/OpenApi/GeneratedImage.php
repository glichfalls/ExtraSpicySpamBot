<?php

namespace App\Entity\OpenApi;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class GeneratedImage
{
    use Id;
    use TimestampableEntity;

    public function __construct()
    {
        $this->generateId();
    }

    #[Column(type: 'text')]
    private string $prompt;

    #[Column(type: 'string')]
    private string $size;

    #[Column(type: 'text', nullable: true)]
    private ?string $imageBase64 = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $publicPath = null;

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): void
    {
        $this->size = $size;
    }

    public function getImageBase64(): ?string
    {
        return $this->imageBase64;
    }

    public function setImageBase64(?string $imageBase64): void
    {
        $this->imageBase64 = $imageBase64;
    }

    public function getPublicPath(): ?string
    {
        return $this->publicPath;
    }

    public function setPublicPath(?string $publicPath): void
    {
        $this->publicPath = $publicPath;
    }

}