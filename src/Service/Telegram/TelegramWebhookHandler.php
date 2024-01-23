<?php

namespace App\Service\Telegram;

use App\Entity\Message\Message;
use App\Repository\MessageRepository;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TelegramBot\Api\Types\Update;

class TelegramWebhookHandler
{

    /**
     * @var iterable<TelegramChatCommand>
     */
    private iterable $handlers;

    private const ALLOW_LIST = ['!ehre', '!jackpot', '!items', '!collection', '!gift'];

    /**
     * @param iterable<TelegramChatCommand> $telegramChatCommands
     * @param TelegramService $telegramBaseService
     * @param MessageRepository $messageRepository
     */
    public function __construct(
        #[TaggedIterator('telegram.chat_command')]
        iterable $telegramChatCommands,
        private readonly TelegramService $telegramBaseService,
        private readonly MessageRepository $messageRepository,
        private readonly HttpClientInterface $httpClient,
    ) {
        $this->handlers = $telegramChatCommands;
    }

    public function handle(Update $update): void
    {
        if ($update->getMessage()?->getChat() !== null) {
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
            if ($messageCount > 10 && (
                str_starts_with($message->getMessage(), '+') ||
                str_starts_with($message->getMessage(), '-') ||
                str_starts_with($message->getMessage(), '!')
            ) && !$this->isCommandAlwaysAllowed($message)) {
                $this->telegramBaseService->imageReplyTo($message, $this->getRandomMeme());
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

    private function isCommandAlwaysAllowed(Message $message): bool
    {
        foreach (self::ALLOW_LIST as $command) {
            if (str_starts_with($message->getMessage(), $command)) {
                return true;
            }
        }
        return false;
    }

    private function getRandomMeme(): string
    {
        $response = $this->httpClient->request('GET', 'https://meme-api.com/gimme');
        $data = $response->toArray();
        return $data['url'];
    }

}
