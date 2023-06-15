<?php

namespace App\Service\OpenApi;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseOpenAiService
{

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected EntityManagerInterface $entityManager,
        private string $openAiApiKey
    )
    {
        $this->httpClient = $httpClient->withOptions([
            'base_uri' => 'https://api.openai.com',
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->openAiApiKey),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    protected function post(string $uri, array $data): array
    {
        try {
            $response = $this->httpClient->request('POST', $uri, [
                'json' => $data,
            ]);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException(sprintf(
                    'OpenAI API returned status code %s (%s)',
                    $response->getStatusCode(),
                    $response->getContent(false),
                ));
            }
            return json_decode($response->getContent(), true);
        } catch (\Throwable $throwable) {
            throw new \RuntimeException(sprintf('Failed to call OpenAI API: %s', $throwable->getMessage()), previous: $throwable);
        }
    }

}