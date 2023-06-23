<?php

namespace App\Entity\Sticker;

use App\Entity\User\User;

class StickerSetFactory
{
    private const BOT_USERNAME = 'ExtraSpicySpamBot';

    public static function create(User $owner, string $name, string $title): StickerSet
    {
        $set = new StickerSet();
        $set->setOwner($owner);
        $set->setName(sprintf('%s_by_%s', $name, self::BOT_USERNAME));
        $set->setTitle($title);
        $set->setStickerFormat('regular');
        return $set;
    }

}