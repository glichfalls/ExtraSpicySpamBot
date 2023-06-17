<?php

namespace App\Telegram\Command\Subscription;

use App\Service\ChatSubscriptionService;
use App\Service\Telegram\TelegramService;
use App\Telegram\Command\AbstractCommandExtension;
use Psr\Log\LoggerInterface;

abstract class AbstractSubscriptionCommand extends AbstractCommandExtension
{

    public function __construct(
        LoggerInterface                   $logger,
        TelegramService                   $telegramService,
        protected ChatSubscriptionService $subscriptionService,
    )
    {
        parent::__construct($logger, $telegramService);
    }

}