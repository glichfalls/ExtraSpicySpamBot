<?php

namespace App\Telegram\Command;

use App\Service\TelegramBaseService;
use BoShurik\TelegramBotBundle\Telegram\Command\AbstractCommand;
use BoShurik\TelegramBotBundle\Telegram\Command\PublicCommandInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\Types\Update;

abstract class AbstractCommandExtension extends AbstractCommand implements PublicCommandInterface
{

    public function __construct(
        protected LoggerInterface $logger,
        protected TelegramBaseService $telegramService
    ) {

    }

    public function isApplicable(Update $update): bool
    {
        try {
            return parent::isApplicable($update);
        } catch (\Throwable) {
            return false;
        }
    }

}