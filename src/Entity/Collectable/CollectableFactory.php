<?php

namespace App\Entity\Collectable;

use App\Entity\Chat\Chat;
use App\Entity\User\User;

class CollectableFactory
{

    public static function create(
        string $name,
        string $description,
        bool $tradeable = true,
        bool $unique = false
    ): Collectable {
        $collectable = new Collectable();
        $collectable->setName($name);
        $collectable->setDescription($description);
        $collectable->setUnique($unique);
        $collectable->setTradeable($tradeable);
        return $collectable;
    }

    public static function instance(
        Collectable $collectable,
        Chat $chat,
        ?User $owner,
        int $price
    ): CollectableItemInstance {
        $instance = new CollectableItemInstance();
        $instance->setCollectable($collectable);
        $instance->setChat($chat);
        $instance->setOwner($owner);
        $instance->setPrice($price);
        $instance->setCreatedAt(new \DateTime());
        $instance->setUpdatedAt(new \DateTime());
        return $instance;
    }

}
