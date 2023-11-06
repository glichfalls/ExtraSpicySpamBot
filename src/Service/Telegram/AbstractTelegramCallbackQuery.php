<?php

namespace App\Service\Telegram;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

abstract class AbstractTelegramCallbackQuery implements TelegramCallbackQueryListener
{

    public function __construct(
        protected EntityManagerInterface $manager,
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected TelegramService $telegramService
    ) {

    }

    protected function getCallbackDataParts(Update $update, $numberOfArgs = 1): array
    {
        // commands should always have at least 2 parts (e.g. 'domain:command:arg1:arg2')
        $parts = explode(':', $update->getCallbackQuery()->getData());
        if (count($parts) !== $numberOfArgs + 2) {
            throw new \InvalidArgumentException(sprintf('Invalid callback data for %s.', static::class));
        }
        return array_slice($parts, 2, $numberOfArgs);
    }

    protected function countCallbackDataParts(Update $update): int
    {
        return count(explode(':', $update->getCallbackQuery()->getData()));
    }

    protected function getCallbackDataId(Update $update): string
    {
        $parts = $this->getCallbackDataParts($update);
        return array_pop($parts);
    }

}
