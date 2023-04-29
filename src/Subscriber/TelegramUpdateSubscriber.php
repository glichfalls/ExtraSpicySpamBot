<?php

namespace App\Subscriber;

use App\Service\TelegramWebhookBaseService;
use BoShurik\TelegramBotBundle\Event\UpdateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TelegramBot\Api\BotApi;
use Throwable;

class TelegramUpdateSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private LoggerInterface $logger,
        private TelegramWebhookBaseService $webhookService,
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
                if ($update->getMessage()->getChat()) {
                    $this->bot->sendMessage(
                        $update->getMessage()->getChat()->getId(),
                        sprintf('sadge :( [%s]', $exception->getMessage()),
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