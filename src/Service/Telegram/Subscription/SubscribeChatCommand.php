<?php declare(strict_types=1);

namespace App\Service\Telegram\Subscription;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\Subscription\ChatSubscription;
use App\Entity\Subscription\SubscriptionTypes;
use App\Repository\ChatSubscriptionRepository;
use App\Service\Telegram\TelegramChatCommand;
use App\Service\Telegram\TelegramService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use TelegramBot\Api\Types\Update;

readonly class SubscribeChatCommand implements TelegramChatCommand
{

    public function __construct(
        private EntityManagerInterface $manager,
        private TelegramService $telegram,
        private ChatSubscriptionRepository $subscriptionRepository,
    ) {
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!subscribe (?<type>[\w_]+)\s*(?<parameter>[\w_]+)?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $type = $matches['type'];
            $parameter = $matches['parameter'] ?? null;
            if (!SubscriptionTypes::isAllowed($type)) {
                throw new \RuntimeException(sprintf('Subscription type %s is not supported', $type));
            }
            $this->subscribe($message->getChat(), $type, $parameter);
            if ($parameter !== null) {
                $this->telegram->replyTo($message, sprintf('Subscribed to %s with %s', $type, $parameter));
            } else {
                $this->telegram->replyTo($message, sprintf('Subscribed to %s', $type));
            }
        } catch (\RuntimeException $exception) {
            $this->telegram->replyTo($message, $exception->getMessage());
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

    public function getSyntax(): string
    {
        return '!subscribe <type> <parameter>';
    }

    public function getDescription(): string
    {
        return 'Subscribe to a subscription';
    }

}
