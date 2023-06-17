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
        private LoggerInterface        $logger,
        private TelegramWebhookService $webhookService,
        private BotApi                 $bot,
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
            if (!$update->getMessage() || !$update->getMessage()->getText()) {
                $this->logger->warning('failed to get text from update');
                return;
            }
            $this->webhookService->handle($update);
            $this->logger->info('Update handled');
        } catch (Throwable $exception) {
            try {
                if ($update->getMessage() && $update->getMessage()->getChat()) {
                    $this->bot->sendMessage(
                        $update->getMessage()->getChat()->getId(),
                        'sadge',
                        replyToMessageId: $update->getMessage()->getMessageId()
                    );
                }
            } catch (Throwable $secondException) {
                $this->logger->emergency('send error message failed', [
                    'exception' => $secondException,
                ]);
            } finally {
                $this->logger->critical('Update handling failed', [
                    'exception' => $exception,
                ]);
            }
        }
    }

}