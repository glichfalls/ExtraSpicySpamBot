<?php

namespace App\Telegram\Command\Honor;

use App\Service\Honor\HonorService;
use App\Service\Telegram\TelegramService;
use App\Telegram\Command\AbstractCommandExtension;
use Psr\Log\LoggerInterface;

abstract class AbstractHonorCommand extends AbstractCommandExtension
{

    public function __construct(
        protected LoggerInterface $logger,
        protected TelegramService $telegramService,
        protected HonorService    $honorService
    )
    {
        parent::__construct($logger, $telegramService);
    }

}