<?php

namespace App\Command\Telegram;

use App\Repository\UserRepository;
use App\Service\OpenApi\OpenAiImageService;
use App\Service\Telegram\TelegramService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('telegram:create-sticker-set')]
class CreateStickerSetCommand extends Command
{

    public function __construct(
        private UserRepository $userRepository,
        private TelegramService $telegramService,
        private OpenAiImageService $aiImageService,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $owner = $this->userRepository->getByTelegramId('1098121923');
        $generatedImage = $this->aiImageService->generateImage($owner, 'A tree', '512x512');
        $path = sprintf('https://extra-spicy-spam.portner.dev/%s', $generatedImage->getPublicPath());
        return $this->telegramService->createStickerSet($owner, 'test', 'Test', $path) !== null;
    }

}