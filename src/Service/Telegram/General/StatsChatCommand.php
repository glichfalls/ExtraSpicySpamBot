<?php

namespace App\Service\Telegram\General;

use App\Entity\Message\Message;
use App\Repository\MessageRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class StatsChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly MessageRepository $messageRepository,
        private readonly string $backendUrl,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!stats\s(?<query>.+)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $query = $matches['query'] ?? null;
            if (!$query) {
                $this->telegramService->replyTo($message, 'Please provide a query.');
            }
            $messages = $this->messageRepository->getTextOccurrencesByUsers($message->getChat(), $query);
            $result = array_map(fn (array $message) => sprintf('%s %s: %dx', $message['firstName'], $message['name'], $message['count']), $messages);
            $result[] = sprintf('Total: %d', array_sum(array_column($messages, 'count')));
            $result = implode(PHP_EOL, $result);
            try {
                $url = sprintf(
                    '%s/telegram/%s/stats?query=%s&ts=%s',
                    $this->backendUrl,
                    $message->getChat()->getId(),
                    urlencode($query),
                    time(),
                );
                $this->telegramService->imageReplyTo($message, $url);
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
            $this->telegramService->replyto($message, $result);
        } catch (\Exception $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
    }

}
