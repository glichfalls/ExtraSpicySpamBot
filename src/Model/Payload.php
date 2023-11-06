<?php

namespace App\Model;

use Doctrine\ORM\Mapping\Column;
use Symfony\Component\Serializer\Annotation\Groups;

trait Payload
{

    #[Column(type: 'json')]
    #[Groups(['payload:read'])]
    private array $payload = [];

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function hasPayloadValue(string $key): bool
    {
        return array_key_exists($key, $this->payload);
    }

    public function getPayloadValue(string $key): mixed
    {
        return $this->payload[$key] ?? null;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function setPayloadValue(string $key, mixed $value): void
    {
        $this->payload[$key] = $value;
    }

}