<?php

namespace App\Service;

use App\Service\OpenApi\TelegramImageGenerationService;
use TelegramBot\Api\Types\Update;

class TelegramWebhookBaseService
{

    public function __construct(
        private TelegramBaseService $telegramBaseService,
        private HonorService $honorBaseService,
        private ChatSubscriptionService $chatSubscriptionService,
        private TelegramImageGenerationService $telegramImageGenerationService,
    )
    {
    }

    public function handle(Update $update): void
    {
        if ($update->getMessage()->getChat()) {
            $message = $this->telegramBaseService->createMessageFromUpdate($update);
            $this->honorBaseService->handle($update, $message);
            $this->chatSubscriptionService->handle($update, $message);
            $this->telegramImageGenerationService->handle($update, $message);
        }
    }

}