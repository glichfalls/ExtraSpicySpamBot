<?php

namespace App\Entity\Sticker;

class StickerFactory
{

    public static function create(StickerSet $set, StickerFile $file, array $emojis): Sticker
    {
        $sticker = new Sticker();
        $sticker->setStickerSet($set);
        $sticker->setFile($file);
        $sticker->setEmojis($emojis);
        return $sticker;
    }

}