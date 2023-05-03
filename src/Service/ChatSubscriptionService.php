<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\Subscription\ChatSubscription;
use App\Entity\Subscription\SubscriptionTypes;
use App\Repository\ChatSubscriptionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use TelegramBot\Api\Types\Update;

class ChatSubscriptionService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private TelegramBaseService $telegramService,
        private ChatSubscriptionRepository $subscriptionRepository,
    )
    {
    }

    public function handle(Update $update, Message $message): void
    {
        if (!$message->getMessage()) {
            return;
        }
        if (preg_match('/^!subscribe (?<type>[\w_]+)\s*(?<parameter>[\w_]+)?$/i', $message->getMessage(), $matches) === 1) {
            try {
                $type = $matches['type'];
                $parameter = $matches['parameter'] ?? null;
                if (!SubscriptionTypes::isAllowed($type)) {
                    throw new \RuntimeException(sprintf('Subscription type %s is not supported', $type));
                }
                $this->subscribe($message->getChat(), $type, $parameter);
                if ($parameter !== null) {
                    $this->telegramService->replyTo($message, sprintf('Subscribed to %s with %s', $type, $parameter));
                } else {
                    $this->telegramService->replyTo($message, sprintf('Subscribed to %s', $type));
                }
            } catch (\RuntimeException $exception) {
                $this->telegramService->replyTo($message, $exception->getMessage());
            }
        }
    }

    public function subscribe(Chat $chat, string $type, ?string $parameter): void
    {
        if ($parameter === null) {
            $existing = $this->subscriptionRepository->findOneBy([
                'chat' => $chat,
                'type' => $type,
            ]);
        } else {
            $existing = $this->subscriptionRepository->findOneBy([
                'chat' => $chat,
                'type' => $type,
                'parameter' => $parameter,
            ]);
        }
        if ($existing) {
            throw new \RuntimeException(sprintf('Already subscribed to %s', $type));
        }
        $subscription = new ChatSubscription();
        $subscription->setChat($chat);
        $subscription->setType($type);
        $subscription->setParameter($parameter);
        $subscription->setCreatedAt(new DateTime());
        $subscription->setUpdatedAt(new DateTime());
        $this->manager->persist($subscription);
        $this->manager->flush();
    }

    public function unsubscribe(string $chatId, string $type = null): void
    {
        $query = ['chatId' => $chatId];
        if ($type !== null) {
            $query['type'] = $type;
        }
        $existing = $this->subscriptionRepository->findBy($query);
        if (count($existing) === 0) {
            throw new \RuntimeException(sprintf('Not subscribed to %s', $type));
        }
        foreach ($existing as $subscription) {
            $this->manager->remove($subscription);
        }
        $this->manager->flush();
    }

}