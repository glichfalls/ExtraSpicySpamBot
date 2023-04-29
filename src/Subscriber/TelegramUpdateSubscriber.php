<?php

namespace App\Subscriber;

use App\Service\TelegramWebhookService;
use BoShurik\TelegramBotBundle\Event\UpdateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TelegramUpdateSubscriber implements EventSubscriberInterface
{

    public function __construct(private LoggerInterface $logger, private TelegramWebhookService $webhookService)
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
        try {
            $this->logger->info('Update received');
            $this->logger->debug('Update:', $event->getUpdate()->toJson(true));
            $this->webhookService->handle($event->getUpdate());
            $this->logger->info('Update handled');
        } catch (\Exception $exception) {
            $this->logger->error('Update handling failed', [
                'exception' => $exception,
            ]);
        }
    }

}