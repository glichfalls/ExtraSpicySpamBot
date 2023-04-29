<?php

namespace App\Service;

use TelegramBot\Api\Types\Update;

class TelegramWebhookBaseService
{

    public function __construct(
        private TelegramBaseService $telegramBaseService,
        private HonorService $honorBaseService,
    )
    {
    }

    public function handle(Update $update): void
    {
        if ($update->getMessage()->getChat()) {
            $message = $this->telegramBaseService->createMessageFromUpdate($update);
            $this->honorBaseService->handle($update, $message);
        }
    }

}