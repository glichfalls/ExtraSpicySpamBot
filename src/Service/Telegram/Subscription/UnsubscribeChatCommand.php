<?php declare(strict_types=1);

namespace App\Service\Telegram\Subscription;

use App\Entity\Message\Message;
use App\Repository\ChatSubscriptionRepository;
use App\Service\Telegram\TelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use TelegramBot\Api\Types\Update;

readonly class UnsubscribeChatCommand implements TelegramChatCommand
{

    public function __construct(
        private EntityManagerInterface $manager,
        private TelegramService $telegram,
        private ChatSubscriptionRepository $subscriptionRepository,
    ) {
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!unsubscribe (?<type>[\w_]+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $query = ['chatId' => $message->getChat()->getId()];
        $type = $matches['type'];
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
        $this->telegram->replyTo($message, sprintf('Unsubscribed from %s', $type));
    }

    public function getSyntax(): string
    {
        return '!unsubscribe <type>';
    }

    public function getDescription(): string
    {
        return 'Unsubscribe from a subscription';
    }

}
