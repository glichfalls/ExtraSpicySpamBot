<?php

namespace App\Entity\OpenApi;

use App\Model\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class GeneratedCompletion
{
    use Id;
    use TimestampableEntity;

    #[Column(type: 'text')]
    public string $prompt;

    #[Column(type: 'text', nullable: true)]
    public ?string $completion = null;

    public function __construct()
    {
        $this->generateId();
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    public function getCompletion(): ?string
    {
        return $this->completion;
    }

    public function setCompletion(?string $completion): void
    {
        $this->completion = $completion;
    }

}