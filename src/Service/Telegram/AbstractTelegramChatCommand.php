<?php

namespace App\Service\Telegram;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTelegramChatCommand implements TelegramChatCommand
{

    public function __construct(
        protected EntityManagerInterface $manager,
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected TelegramService $telegramService
    )
    {

    }

    public function getDescription(): string
    {
        $className = substr(strrchr(static::class, "\\"), 1);
        $className = preg_replace('/ChatCommand$/', '', $className);
        $parts = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY);
        return implode(' ', $parts);
    }

    public function getSyntax(): string
    {
        return 'Syntax not set';
    }

}