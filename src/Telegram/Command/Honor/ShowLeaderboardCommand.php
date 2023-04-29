<?php

namespace App\Telegram\Command\Honor;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class ShowLeaderboardCommand extends AbstractHonorCommand
{

    public function getName(): string
    {
        return '/show_leaderboard';
    }

    public function getDescription(): string
    {
        return 'show the leaderboard';
    }

    public function execute(BotApi $api, Update $update): void
    {
        $message = $this->telegramService->createMessageFromUpdate($update);
        $this->honorService->showLeaderboard($message);
    }

}