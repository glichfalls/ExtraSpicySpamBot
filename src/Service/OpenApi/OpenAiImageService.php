<?php

namespace App\Service\OpenApi;

use App\Entity\OpenApi\GeneratedImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiImageService extends BaseOpenAiService
{

    private Filesystem $filesystem;

    public function __construct(private KernelInterface $kernel, HttpClientInterface $httpClient, EntityManagerInterface $entityManager, string $openAiApiKey)
    {
        parent::__construct($httpClient, $entityManager, $openAiApiKey);
        $this->filesystem = new Filesystem();
    }

    private function createGeneratedImage(string $prompt, string $size): GeneratedImage
    {
        $generatedImage = new GeneratedImage();
        $generatedImage->setPrompt($prompt);
        $generatedImage->setSize($size);
        $generatedImage->setCreatedAt(new \DateTime());
        $generatedImage->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($generatedImage);
        $this->entityManager->flush();
        return $generatedImage;
    }

    private function saveGeneratedImage(GeneratedImage $generatedImage, string $base64Image): void
    {
        $generatedImage->setImageBase64($base64Image);
        $publicPath = sprintf('/generated-images/%s.png', $generatedImage->getId());
        $serverPath = sprintf('%s/public/%s', $this->kernel->getProjectDir(), $publicPath);
        $this->filesystem->dumpFile($serverPath, base64_decode($base64Image));
        $generatedImage->setPublicPath($publicPath);
        $this->entityManager->flush();
    }

    public function generateImage($prompt, $size = '256x256'): GeneratedImage
    {
        $generatedImage = $this->createGeneratedImage($prompt, $size);
        $data = $this->post('/v1/images/generations', [
            'prompt' => $prompt,
            'n' => 1,
            'size' => '256x256',
            'response_format' => 'b64_json',
        ]);
        $this->saveGeneratedImage($generatedImage, $data['data'][0]['b64_json']);
        return $generatedImage;
    }

}