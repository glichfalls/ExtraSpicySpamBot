<?php

namespace App\Service\Telegram;

use App\Repository\MessageRepository;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use TelegramBot\Api\Types\Update;

class TelegramWebhookHandler
{

    /**
     * @var iterable<TelegramChatCommand>
     */
    private iterable $handlers;

    public function __construct(
        #[TaggedIterator('telegram.chat_command')]
        iterable $telegramChatCommands,
        private readonly TelegramService $telegramBaseService,
        private readonly MessageRepository $messageRepository,
    ) {
        $this->handlers = $telegramChatCommands;
    }

    public function handle(Update $update): void
    {
        if ($update->getMessage()->getChat()) {
            $message = $this->telegramBaseService->createMessageFromUpdate($update);
            $qb = $this->messageRepository->createQueryBuilder('m');
            $messageCount = $qb
                ->select('count(m.id)')
                ->where('m.chat = :chat')
                ->andWhere('m.createdAt > :createdAt')
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like('m.message', '\'!%\''),
                    $qb->expr()->like('m.message', '\'+%\''),
                    $qb->expr()->like('m.message', '\'-%\''),
                ))
                ->andWhere('m.user = :user')
                ->setParameter('chat', $message->getChat())
                ->setParameter('user', $message->getUser())
                ->setParameter('createdAt', new \DateTime('-1 minute'))
                ->getQuery()
                ->getSingleScalarResult();
            if ($messageCount > 10 && str_starts_with($message->getMessage(), '!')) {
                $this->telegramBaseService->replyTo($message, '🤫');
                return;
            }
            foreach ($this->handlers as $telegramChatCommand) {
                $matches = [];
                if ($telegramChatCommand->matches($update, $message, $matches)) {
                    $telegramChatCommand->handle($update, $message, $matches);
                }
            }
        }
    }

}
