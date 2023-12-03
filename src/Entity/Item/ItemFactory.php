<?php

namespace App\Entity\Item;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Season\Season;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\User\User;
use DateTimeInterface;

class ItemFactory
{

    public static function create(
        string $name,
        string $description,
        ItemRarity $rarity,
        bool $permanent,
        array $attributes = [],
        ?EffectCollection $effects = null,
    ): Item {
        $item = new Item();
        $item->setName($name);
        $item->setDescription($description);
        $item->setRarity($rarity);
        $item->setPermanent($permanent);
        $item->setAttributes($attributes);
        $item->setEffects($effects ?? new EffectCollection());
        return $item;
    }

    public static function instance(
        Season $season,
        Item $item,
        Chat $chat,
        ?User $owner,
        bool $tradeable,
        ?DateTimeInterface $expiresAt = null,
    ): ItemInstance {
        $instance = new ItemInstance();
        $instance->setSeason($season);
        $instance->setItem($item);
        $instance->setChat($chat);
        $instance->setOwner($owner);
        if ($item->isPermanent()) {
            if ($expiresAt !== null) {
                throw new \InvalidArgumentException('Permanent items cannot expire');
            }
            $instance->setExpiresAt(null);
        } else {
            if ($expiresAt === null) {
                throw new \InvalidArgumentException('Non-permanent items must have an expiration date');
            }
            $instance->setExpiresAt($expiresAt);
        }
        $instance->setTradeable($tradeable);
        $instance->setCreatedAt(new \DateTime());
        $instance->setUpdatedAt(new \DateTime());
        return $instance;
    }

}
