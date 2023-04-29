<?php

namespace App\Telegram\Command\Honor;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class ShowHonorCommand extends AbstractHonorCommand
{

    public function getName(): string
    {
        return 'show_honor';
    }

    public function execute(BotApi $api, Update $update): void
    {
        $message = $this->telegramService->createMessageFromUpdate($update);
        $this->honorService->showHonor($message);
    }

}