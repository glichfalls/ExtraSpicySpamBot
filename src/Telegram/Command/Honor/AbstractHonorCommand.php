<?php

namespace App\Telegram\Command\Honor;

use App\Service\HonorService;
use App\Service\TelegramBaseService;
use BoShurik\TelegramBotBundle\Telegram\Command\AbstractCommand;

abstract class AbstractHonorCommand extends AbstractCommand
{

    public function __construct(
        protected TelegramBaseService $telegramService,
        protected HonorService $honorService,
    )
    {

    }

}