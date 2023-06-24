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
        return $this->telegramService->createStickerSet($owner, 'test', 'Test', '🤖', '/generated-images/9f8980a8-3d27-4819-be95-b8ebd2cad736.png') !== null;
    }

}