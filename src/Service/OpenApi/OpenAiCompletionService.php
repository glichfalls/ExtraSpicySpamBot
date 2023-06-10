<?php

namespace App\Service\OpenApi;

use App\Entity\OpenApi\GeneratedCompletion;

class OpenAiCompletionService extends BaseOpenAiService
{
    public const IMAGE_PATH = 'public/generated-images';

    private const MODEL_35 = 'gpt-3.5-turbo';
    private const MODEL_4 = 'gpt-4';

    public function fineTune()
    {
        $this->post('/fine-tunes', [
            'model' => self::MODEL_35,
            'training_file' => '',
        ]);
    }

    private function createCompletion(string $prompt): GeneratedCompletion
    {
        $completion = new GeneratedCompletion();
        $completion->setPrompt($prompt);
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

}