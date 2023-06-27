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
        return 'Description not set';
    }

    public function getSyntax(): string
    {
        return 'Syntax not set';
    }

}