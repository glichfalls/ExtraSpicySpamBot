<?php

namespace App\Service\Telegram\OpenAi;

use App\Entity\Message\Message;
use App\Service\OpenApi\OpenAiImageService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GenerateImageChatCommand extends AbstractTelegramChatCommand
{

    private const SIZES = [
        's' => '256x256',
        'm' => '512x512',
        'l' => '1024x1024',
    ];

    public function __construct(
        EntityManagerInterface     $manager,
        TranslatorInterface        $translator,
        LoggerInterface            $logger,
        TelegramService            $telegramService,
        private OpenAiImageService $openAiImageService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^aiimg\s(?<size>[sml])?\s?(?<prompt>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $size = $this->getSize($matches);
            $generatedImage = $this->openAiImageService->generateImage($matches['prompt'], $size);
            $this->telegramService->imageReplyTo(
                $message,
                sprintf('https://%s/%s', $_SERVER['HTTP_HOST'], $generatedImage->getPublicPath()),
            );
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage());
            $this->telegramService->replyTo($message, $th->getMessage());
        }
    }

    private function getSize(array $matches): string
    {
        if (array_key_exists('size', $matches)) {
            $size = strtolower($matches['size']);
            if (array_key_exists($size, self::SIZES)) {
                return self::SIZES[$size];
            }
        }
        return self::SIZES['s'];
    }

}