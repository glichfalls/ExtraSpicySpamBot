<?php

namespace App\Service\OpenApi;

use App\Entity\OpenApi\GeneratedCompletion;

class OpenAiCompletionService extends BaseOpenAiService
{

    private function createCompletion(string $prompt): GeneratedCompletion
    {
        $completion = new GeneratedCompletion();
        $completion->setPrompt($prompt);
        $completion->setCreatedAt(new \DateTime());
        $completion->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($completion);
        $this->entityManager->flush();
        return $completion;
    }

    public function completion(string $message): GeneratedCompletion
    {
        $completion = $this->createCompletion($message);
        $data = $this->post('/v1/completions', [
            'model' => 'text-davinci-003',
            'prompt' => $message,
            'max_tokens' => 50,
        ]);
        $completion->setCompletion($data['choices'][0]['text']);
        $this->entityManager->flush();
        return $completion;
    }

    public function chatCompletion(string $prompt, array $messages): GeneratedCompletion
    {
        $completion = $this->createCompletion($prompt);
        $messages[] = ['role' => 'user', 'content' => $prompt];
        $data = $this->post('/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => array_values($messages),
            'max_tokens' => 50,
        ]);
        $completion->setCompletion($data['choices'][0]['message']['content']);
        $this->entityManager->flush();
        return $completion;
    }

}