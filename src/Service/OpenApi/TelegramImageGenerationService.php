<?php

namespace App\Service\OpenApi;

use App\Entity\Message\Message;
use App\Repository\UserRepository;
use App\Service\TelegramBaseService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\Types\Update;

class TelegramImageGenerationService
{

    public function __construct(
        private TelegramBaseService $telegramService,
        private OpenAiImageService $openAiImageService,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private EntityManagerInterface $manager,
    )
    {

    }

    public function handle(Update $update, Message $message): void
    {
        $text = $message->getMessage();
        if (preg_match('/^ai\s*img (?<prompt>.+)$/i', $text, $matches) === 1) {
            $prompt = $matches['prompt'];
            $generatedImage = $this->openAiImageService->generateImage($prompt);
            $this->telegramService->videoReplyTo($message, $generatedImage->getPublicPath());
        }
    }

}