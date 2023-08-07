<?php

namespace App\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

trait Id
{

    #[ORM\Id]
    #[Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['public:read'])]
    protected UuidInterface $id;

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    private function generateId(): void
    {
        $this->id = Uuid::uuid4();
    }

    public function __clone(): void
    {
        $this->generateId();
    }

}