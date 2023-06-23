<?php

namespace App\Service\Telegram\OpenAi;

use App\Entity\Message\Message;
use App\Repository\GeneratedImageRepository;
use App\Service\OpenApi\OpenAiImageService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GenerateStickerChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface     $manager,
        TranslatorInterface        $translator,
        LoggerInterface            $logger,
        TelegramService            $telegramService,
        private OpenAiImageService $openAiImageService,
        private GeneratedImageRepository $generatedImageRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^aisticker\s(?<prompt>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {

    }

}