<?php

namespace App\Service;

use App\Entity\Message\Message;
use App\Entity\Subscription\ChatSubscription;
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
        if (preg_match('/^!subscribe (?<type>.+)$/i', $message->getMessage(), $matches) === 1) {
            try {
                $type = $matches['type'];
                $this->subscribe($message->getChat()->getChatId(), $type);
                $this->telegramService->replyTo($message, sprintf('Subscribed to %s', $type));
            } catch (\RuntimeException $exception) {
                $this->telegramService->replyTo($message, $exception->getMessage());
            }
        }
    }

    public function subscribe(string $chatId, string $type): void
    {
        $existing = $this->subscriptionRepository->findOneBy([
            'chatId' => $chatId,
            'type' => $type,
        ]);
        if ($existing) {
            throw new \RuntimeException(sprintf('Already subscribed to %s', $type));
        }
        $subscription = new ChatSubscription();
        $subscription->setChatId($chatId);
        $subscription->setType($type);
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