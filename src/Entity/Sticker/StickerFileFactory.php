<?php

namespace App\Entity\Sticker;

use App\Entity\User\User;

class StickerFileFactory
{

    public static function create(User $owner, string $sticker): StickerFile
    {
        $stickerFile = new StickerFile();
        $stickerFile->setOwner($owner);
        $stickerFile->setSticker($sticker);
        $stickerFile->setStickerFormat('static');
        return $stickerFile;
    }

}