<?php

namespace App\Service\Telegram;

use App\Service\TelegramBaseService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTelegramChatCommand implements TelegramChatCommand
{

    public function __construct(
        protected EntityManagerInterface $manager,
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected TelegramBaseService $telegramService
    )
    {

    }

}