<?php

namespace App\Entity\Item\Challenge;

use App\Entity\Item\ItemInstance;
use App\Model\Id;
use App\Model\Payload;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Entity]
class ItemChallenge
{
    use Id;
    use TimestampableEntity;
    use Payload;

    #[ManyToOne(targetEntity: ItemInstance::class)]
    private ItemInstance $instance;

    #[Column(type: 'boolean', nullable: true)]
    private ?bool $success = null;

    public function __construct()
    {
        $this->generateId();
    }

    public function getInstance(): ItemInstance
    {
        return $this->instance;
    }

    public function setInstance(ItemInstance $instance): void
    {
        $this->instance = $instance;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(?bool $success): void
    {
        $this->success = $success;
    }

}