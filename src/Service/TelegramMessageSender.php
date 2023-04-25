<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class TelegramMessageSender
{

    public function __construct(private LoggerInterface $logger, private BotApi $bot)
    {

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