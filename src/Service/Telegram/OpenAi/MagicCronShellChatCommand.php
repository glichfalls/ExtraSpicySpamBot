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

class MagicCronShellChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface          $manager,
        TranslatorInterface             $translator,
        LoggerInterface                 $logger,
        TelegramService                 $telegramService,
        private OpenAiCompletionService $completionService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^magische miesmuschel\s*(?<prompt>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $answer = $this->completionService->chatCompletion($matches['prompt'], $this->getMessages());
            $this->telegramService->replyTo($message, $answer->getCompletion());
        } catch (\Throwable $th) {
            $this->telegramService->replyTo($message, $th->getMessage());
        }
    }

    private function getMessages(): array
    {
        return [
            ['role' => 'system', 'content' => 'Du bist die Magische Miesmuschel. Du solltest Fragen mit kurzen einfachen Antworten beantworten.'],
            ['role' => 'system', 'content' => 'Die Antwort muss nicht unbedingt korrekt sein, ein einfaches Ja oder Nein genügt in manchen fällen'],
            ['role' => 'system', 'content' => 'Wenn du dir nicht sicher bist sag einfach zufällig "Ja" oder "Nein"'],
            ['role' => 'user', 'content' => 'Magische Miesmuschel, werde ich jemals heiraten?'],
            ['role' => 'assistant', 'content' => 'Eines Tages vielleicht!'],
            ['role' => 'user', 'content' => 'Oh, magische Miesmuschel, was sollen wir tun, um aus dem Urwald herauszukommen?'],
            ['role' => 'assistant', 'content' => 'Gar nichts.'],
            ['role' => 'user', 'content' => 'Ich frag mich, ob ich zuerst diese leckeren Spaghetti oder den Truthahn essen soll'],
            ['role' => 'assistant', 'content' => 'Keins von beiden.'],
            ['role' => 'user', 'content' => 'Na dann, und was ist mit der Suppe?'],
            ['role' => 'assistant', 'content' => 'Ich glaub eher nicht.'],
            ['role' => 'user', 'content' => 'Krieg ich überhaupt was zu essen?'],
            ['role' => 'assistant', 'content' => 'Nein.'],
        ];
    }

    public function getHelp(): string
    {
        return 'magische miesmuschel <prompt>   answers your question';
    }

}