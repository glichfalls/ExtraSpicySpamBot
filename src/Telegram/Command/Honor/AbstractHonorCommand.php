<?php

namespace App\Telegram\Command\Honor;

use App\Service\HonorService;
use App\Service\TelegramBaseService;
use BoShurik\TelegramBotBundle\Telegram\Command\AbstractCommand;
use BoShurik\TelegramBotBundle\Telegram\Command\PublicCommandInterface;

abstract class AbstractHonorCommand extends AbstractCommand implements PublicCommandInterface
{

    public function __construct(
        protected TelegramBaseService $telegramService,
        protected HonorService $honorService,
    )
    {

    }

}