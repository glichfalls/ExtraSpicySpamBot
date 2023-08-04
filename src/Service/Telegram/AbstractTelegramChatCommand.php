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
        $class = static::class;
        // remove ChatCommand from end
        $class = substr($class, 0, -strlen('ChatCommand'));
        // split camel case
        $words = preg_split('/(?=[A-Z])/', $class);
        return implode(' ', $words);
    }

    public function getSyntax(): string
    {
        return 'Syntax not set';
    }

}