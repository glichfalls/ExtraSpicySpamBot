<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaVideo;
use TelegramBot\Api\Types\Message;

class TelegramMessageSender
{

    public function __construct(
        private LoggerInterface $logger,
        private BotApi $bot,
        private string $extraSpicySpamChatId,
    )
    {

    }

    public function video(string $url): ?array
    {
        try {
            $media = new ArrayOfInputMedia();
            $media->addItem(new InputMediaVideo($url));
            return $this->bot->sendMediaGroup($this->extraSpicySpamChatId, $media);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }

    public function spam(string $text): ?Message
    {
        return $this->send($this->extraSpicySpamChatId, $text);
    }

    public function send(string $chatId, string $text): ?Message
    {
        try {
            return $this->bot->sendMessage($chatId, $text);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }

}