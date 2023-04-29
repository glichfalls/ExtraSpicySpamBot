<?php

namespace App\Subscriber;

use App\Service\TelegramWebhookService;
use BoShurik\TelegramBotBundle\Event\UpdateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TelegramBot\Api\BotApi;
use Throwable;

class TelegramUpdateSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private LoggerInterface $logger,
        private TelegramWebhookService $webhookService,
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
        $update = $event->getUpdate();
        try {
            $this->logger->info('Update received', [
                'update' => $update->toJson(true)
            ]);
            $this->webhookService->handle($update);
            $this->logger->info('Update handled');
        } catch (Throwable $exception) {
            try {
                $this->bot->sendMessage(
                    $update->getMessage()->getChat()->getId(),
                    sprintf('sadge :( [%s]', $exception->getMessage()),
                    replyToMessageId: $update->getMessage()->getMessageId()
                );
            } catch (Throwable $exception) {
                $this->logger->error('send error message failed', [
                    'exception' => $exception,
                ]);
            } finally {
                $this->logger->error('Update handling failed', [
                    'exception' => $exception,
                ]);
            }
        }
    }

}