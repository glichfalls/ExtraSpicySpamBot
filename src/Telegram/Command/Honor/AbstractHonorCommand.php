<?php

namespace App\Telegram\Command\Honor;

use App\Service\HonorService;
use App\Service\TelegramBaseService;
use App\Telegram\Command\AbstractCommandExtension;
use Psr\Log\LoggerInterface;

abstract class AbstractHonorCommand extends AbstractCommandExtension
{

    public function __construct(
        protected LoggerInterface $logger,
        protected TelegramBaseService $telegramService,
        protected HonorService $honorService
    )
    {
        parent::__construct($logger, $telegramService);
    }

}