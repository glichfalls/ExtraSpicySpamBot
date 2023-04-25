<?php

namespace App\Subscriber;

use BoShurik\TelegramBotBundle\Event\UpdateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TelegramBot\Api\BotApi;

class TelegramUpdateSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private LoggerInterface $logger,
        private BotApi $bot,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UpdateEvent::class => 'onUpdate',
        ];
    }

    public function onUpdate(UpdateEvent $event): void
    {
        $chatId = $event->getUpdate()->getMessage()->getChat()->getId();
        $this->logger->debug('Chat ID: ' . $chatId);
        $this->bot->sendMessage($chatId, $chatId);
    }

}