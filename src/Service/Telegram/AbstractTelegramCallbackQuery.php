<?php

namespace App\Service\Telegram;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

abstract class AbstractTelegramCallbackQuery implements TelegramCallbackQueryListener
{

    public function __construct(
        protected EntityManagerInterface $manager,
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected TelegramService $telegramService
    ) {

    }

    protected function createKeyboard(array $buttons): InlineKeyboardMarkup
    {
        $keyboard = [];
        $row = [];
        foreach ($buttons as $button) {
            $row[] = $button;
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        if (count($row) > 0) {
            $keyboard[] = $row;
        }
        return new InlineKeyboardMarkup($keyboard);
    }

}
