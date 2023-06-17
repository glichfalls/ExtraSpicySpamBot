<?php

namespace App\Service\Telegram\Subscription;

use App\Entity\Message\Message;
use App\Repository\ChatSubscriptionRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\TelegramBaseService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class UnsubscribeChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramBaseService $telegramService,
        private ChatSubscriptionRepository $subscriptionRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
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
    }

}