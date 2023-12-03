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
            $messageCount = $this->messageRepository->createQueryBuilder('m')
                ->select('count(m.id)')
                ->where('m.chat = :chat')
                ->andWhere('m.createdAt > :createdAt')
                ->andWhere('m.message LIKE \'!%\'')
                ->setParameter('chat', $message->getChat())
                ->setParameter('createdAt', new \DateTime('-1 minute'))
                ->getQuery()
                ->getSingleScalarResult();
            if ($messageCount > 10) {
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
