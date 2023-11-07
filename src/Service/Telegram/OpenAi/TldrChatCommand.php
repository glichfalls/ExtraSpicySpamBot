<?php

namespace App\Service\Telegram\OpenAi;

use App\Entity\Message\Message;
use App\Service\OpenApi\OpenAiCompletionService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class TldrChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly OpenAiCompletionService $completionService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^tldr\s?(?<prompt>.*)/is', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $replyMessage = $update->getMessage()->getReplyToMessage();
            if ($replyMessage !== null) {
                $prompt = $replyMessage->getText();
            } else {
                $prompt = $matches['prompt'];
            }
            if (!$prompt) {
                $this->telegramService->replyTo($message, 'no prompt given');
                return;
            }
            $answer = $this->completionService->chatCompletion($prompt, [
                ['role' => 'system', 'content' => 'summarize the following text']
            ], maxTokens: null);
            $this->telegramService->replyTo($message, $answer->getCompletion());
        } catch (\Throwable $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
    }

}