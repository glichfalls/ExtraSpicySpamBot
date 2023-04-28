<?php

namespace App\Subscriber;

use App\Service\TelegramWebhookService;
use BoShurik\TelegramBotBundle\Event\UpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TelegramUpdateSubscriber implements EventSubscriberInterface
{

    public function __construct(private TelegramWebhookService $webhookService)
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
        $this->webhookService->handle($event->getUpdate());
    }

}