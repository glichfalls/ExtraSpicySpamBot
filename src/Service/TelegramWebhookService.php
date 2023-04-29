<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Entity\User\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class TelegramWebhookService
{

    private EntityRepository $chatRepository;
    private EntityRepository $userRepository;

    public function __construct(
        private BotApi $bot,
        private EntityManagerInterface $manager,
        private LoggerInterface $logger,
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
        if (!$chat || !$sender) {
            $this->logger->info('chat or sender not found');
            $chatId = $update->getMessage()?->getChat()?->getId();
            if ($chatId) {
                $this->bot->sendMessage($chatId, 'failed to process update');
            }
            return;
        }
        if ($update->getMessage()) {
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
        } else {
            $this->bot->sendMessage($chat->getChatId(), 'ok');
        }
    }

    private function createChatIfNotExist(Update $update): ?Chat
    {
        if ($update->getMessage()?->getChat()?->getId() === null) {
            return null;
        }
        $chat = $this->chatRepository->findOneBy(['chatId' => $update->getMessage()->getChat()->getId()]);
        if (!$chat) {
            $chat = ChatFactory::createFromUpdate($update);
            $this->manager->persist($chat);
            $this->manager->flush();
        }
        return $chat;
    }

    private function createUserIfNotExist(Update $update): ?User
    {
        if ($update->getMessage()?->getFrom()?->getId() === null) {
            return null;
        }
        $user = $this->userRepository->findOneBy(['telegramUserId' => $update->getMessage()->getFrom()->getId()]);
        if (!$user) {
            $user = UserFactory::createFromUpdate($update);
            $this->manager->persist($user);
            $this->manager->flush();
        }
        return $user;
    }

}