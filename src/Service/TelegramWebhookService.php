<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Entity\User\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class TelegramWebhookService
{

    private EntityRepository $chatRepository;
    private EntityRepository $userRepository;

    public function __construct(
        private BotApi $bot,
        private EntityManagerInterface $manager,
        private HonorService $honorService,
    )
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            $this->bot->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        }
        $this->chatRepository = $this->manager->getRepository(Chat::class);
        $this->userRepository = $this->manager->getRepository(User::class);
    }

    public function handle(Update $update): void
    {
        $chat = $this->createChatIfNotExist($update);
        $sender = $this->createUserIfNotExist($update);
        $message = new Message();
        $message->setChat($chat);
        $message->setUser($sender);
        $message->setMessage($update->getMessage()->getText());
        $message->setCreatedAt(new \DateTime());
        $message->setUpdatedAt(new \DateTime());
        $this->manager->persist($message);
        $this->manager->flush();

        $this->honorService->handle($update, $message);

        $successMessage = sprintf('saved "%s"', $message->getMessage());
        $this->bot->sendMessage($chat->getChatId(), $successMessage, replyToMessageId: $update->getMessage()->getMessageId());
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

    private function createUserIfNotExist(Update $update): User
    {
        $user = $this->userRepository->findOneBy(['telegramUserId' => $update->getMessage()->getFrom()->getId()]);
        if (!$user) {
            $user = UserFactory::createFromUpdate($update);
            $this->manager->persist($user);
            $this->manager->flush();
        }
        return $user;
    }

}