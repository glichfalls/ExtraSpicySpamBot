<?php

namespace App\Command;

use App\Repository\ChatRepository;
use App\Service\TelegramBaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('telegram:memes:friday-sailor')]
class FridaySailorCommand extends Command
{

    public function __construct(
        private TelegramBaseService $telegramService,
        private ChatRepository $chatRepository,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $chats = $this->chatRepository->findAll();
        foreach ($chats as $chat) {
            $this->telegramService->sendVideo($chat->getId(), 'https://extra-spicy-spam.portner.dev/assets/video/friday-sailor.mp4');
        }
    }

}