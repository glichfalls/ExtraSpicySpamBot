<?php

namespace App\Subscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\Item\ItemInstance;
use App\Service\Telegram\TelegramService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class ItemSubscriber implements EventSubscriberInterface
{

    public function __construct(private TelegramService $telegram, private string $appHost)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onPostWrite', EventPriorities::POST_WRITE],
        ];
    }

    public function onPostWrite(ViewEvent $event): void
    {
        $entity = $event->getControllerResult();
        if (!$entity instanceof ItemInstance) {
            return;
        }
        $this->telegram->sendImage(
            $entity->getChat()->getChatId(),
            $entity->getItem()->getImagePublicUrl($this->appHost),
            caption: <<<CAPTION
                A new item has dropped
                {$entity->getItem()->getRarity()->name()} {$entity->getItem()->getName()}
                CAPTION,
            threadId: $entity->getChat()->getConfig()->getDefaultThreadId(),
        );
    }

}