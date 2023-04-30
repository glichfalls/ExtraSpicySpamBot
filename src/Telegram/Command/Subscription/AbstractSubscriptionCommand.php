<?php

namespace App\Telegram\Command\Subscription;

use App\Service\ChatSubscriptionService;
use App\Service\TelegramBaseService;
use App\Telegram\Command\AbstractCommandExtension;
use Psr\Log\LoggerInterface;

abstract class AbstractSubscriptionCommand extends AbstractCommandExtension
{

    public function __construct(
        LoggerInterface $logger,
        TelegramBaseService $telegramService,
        protected ChatSubscriptionService $subscriptionService,
    )
    {
        parent::__construct($logger, $telegramService);
    }

}