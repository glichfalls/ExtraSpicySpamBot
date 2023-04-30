<?php

namespace App\Telegram\Command\Subscription;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class SubscribeChatCommand extends AbstractSubscriptionCommand
{

    public function getName(): string
    {
        return '/subscribe_chat';
    }

    public function getDescription(): string
    {
        return 'Subscribe the current chat to a type of notification';
    }

    public function execute(BotApi $api, Update $update): void
    {
        $message = $this->telegramService->createMessageFromUpdate($update);
        try {
            $this->subscriptionService->subscribe($message->getChat(), 'test');
            $this->telegramService->replyTo($message, 'Subscribed to test');
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->telegramService->replyTo($message, 'Something went wrong');
        }
    }

}