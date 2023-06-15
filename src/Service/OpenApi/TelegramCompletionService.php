<?php

namespace App\Service\OpenApi;

use App\Entity\Message\Message;
use App\Service\TelegramBaseService;
use TelegramBot\Api\Types\Update;

class TelegramCompletionService
{

    public function __construct(private TelegramBaseService $telegramService, private OpenAiCompletionService $openAiCompletionService)
    {

    }

    public function handle(Update $update, Message $message): void
    {
        $text = $message->getMessage();
        if (preg_match('/^magische miesmuschel\s*(?<prompt>.+)$/i', $text, $matches) === 1) {
            try {
                $prompt = $matches['prompt'];
                $answer = $this->magicConchShell($prompt);
                $this->telegramService->replyTo($message, $answer);
            } catch (\Throwable $th) {
                $this->telegramService->replyTo($message, $th->getMessage());
            }
        }
    }

    private function magicConchShell(string $question): string
    {
        $messages = [
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
        try {
            $response = $this->openAiCompletionService->chatCompletion($question, $messages);
        } catch (\Throwable $th) {
            dump($th);
            return $th->getMessage();
        }
        dump($response);
        return $response->getCompletion();
    }

}