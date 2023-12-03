<?php

namespace App\Subscriber;

use App\Event\Effect\ApplyEffectEvent;
use App\Service\Items\ItemEffectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class EffectSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private ItemEffectService $effectService,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ApplyEffectEvent::NAME => 'onApplyEffect',
        ];
    }

    public function onApplyEffect(ApplyEffectEvent $event): void
    {
        $effects = $this->effectService->getEffectsByUserAndType(
            $event->getUser(),
            $event->getChat(),
            $event->getEffectTypes(),
        );
        if ($event->isPositive()) {
            $event->setAmount($effects->apply($event->getAmount()));
        } else {
            $event->setAmount($effects->applyNegative($event->getAmount()));
        }
    }

}