<?php

namespace App\Telegram\Command;

use BoShurik\TelegramBotBundle\Telegram\Command\AbstractCommand;
use TelegramBot\Api\Types\Update;

abstract class AbstractCommandExtension extends AbstractCommand
{

    public function isApplicable(Update $update): bool
    {
        try {
            return parent::isApplicable($update);
        } catch (\Throwable) {
            return false;
        }
    }

}