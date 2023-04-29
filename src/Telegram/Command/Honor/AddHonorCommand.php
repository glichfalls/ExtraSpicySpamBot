<?php

namespace App\Telegram\Command\Honor;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class AddHonorCommand extends AbstractHonorCommand
{

    public function getName(): string
    {
        return 'add_honor';
    }

    public function execute(BotApi $api, Update $update): void
    {
        $parameter = $this->getCommandParameters($update);
    }
}