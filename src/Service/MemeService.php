<?php

namespace App\Service;

use App\Service\Telegram\TelegramService;

readonly class MemeService
{

    public function __construct(
        private TelegramService $telegramService,
        private string $extraSpicySpamChatId,
    ) {

    }

    public function weekdaySailor(string $weekday): void
    {
        $this->telegramService->sendVideo(
            $this->extraSpicySpamChatId,
            sprintf('https://extra-spicy-spam.portner.dev/assets/video/%s-sailor.mp4', strtolower($weekday))
        );
    }

    public function fridaySailor(): void
    {
        $this->telegramService->sendVideo(
            $this->extraSpicySpamChatId,
            'https://extra-spicy-spam.portner.dev/assets/video/friday-sailor.mp4'
        );
    }

    public function saturdaySailor(): void
    {
        $this->telegramService->sendVideo(
            $this->extraSpicySpamChatId,
            'https://extra-spicy-spam.portner.dev/assets/video/saturday-sailor.mp4'
        );
    }

}
