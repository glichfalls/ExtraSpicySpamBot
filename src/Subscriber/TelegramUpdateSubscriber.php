<?php

namespace App\Subscriber;

use App\Service\Telegram\TelegramWebhookHandler;
use BoShurik\TelegramBotBundle\Event\UpdateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\HttpException;
use Throwable;

class TelegramUpdateSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private LoggerInterface        $logger,
        private TelegramWebhookHandler $webhookService,
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
        } catch (HttpException $exception) {
            // telegram api seems to have some downtime sometimes
            // nothing we can do about it
            $this->logger->error('failed to call telegram api', [
                'exception' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            try {
                if ($update->getMessage() && $update->getMessage()->getChat()) {
                    $this->bot->sendMessage(
                        $update->getMessage()->getChat()->getId(),
                        '💀',
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