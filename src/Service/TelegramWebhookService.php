<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class TelegramWebhookService
{

    private EntityRepository $chatRepository;

    public function __construct(private BotApi $bot, private EntityManagerInterface $manager)
    {
        $this->chatRepository = $this->manager->getRepository(Chat::class);
    }

    public function handle(Update $update): void
    {
        $chat = $this->createChatIfNotExist($update);
        $this->bot->sendMessage($chat->getChatId(), 'Hello world!');
    }

    private function createChatIfNotExist(Update $update): Chat
    {
        $chat = $this->chatRepository->findOneBy(['chatId' => $update->getMessage()->getChat()->getId()]);
        if (!$chat) {
            $chat = ChatFactory::createFromUpdate($update);
            $this->manager->persist($chat);
            $this->manager->flush();
        }
        return $chat;
    }

}