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

    public function __construct(
        private BotApi $bot,
        private EntityManagerInterface $manager,
        private LoggerInterface $logger,
        private HonorService $honorService,
        private UserService $userService,
    )
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            $this->bot->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        }
        $this->chatRepository = $this->manager->getRepository(Chat::class);
    }

    public function handle(Update $update): void
    {
        try {
            $chat = $this->createChatIfNotExist($update);
            $sender = $this->userService->createSender($update);
            if (!$chat || !$sender) {
                $this->logger->info('chat or sender not found');
                $chatId = $update->getMessage()?->getChat()?->getId();
                if ($chatId) {
                    $this->bot->sendMessage($chatId, 'failed to process update');
                }
                return;
            }
            if ($update->getMessage() && $update->getMessage()->getText()) {
                $message = new Message();
                $message->setChat($chat);
                $message->setUser($sender);
                $message->setMessage($update->getMessage()->getText());
                $message->setCreatedAt(new \DateTime());
                $message->setUpdatedAt(new \DateTime());
                $this->manager->persist($message);
                $this->manager->flush();

                $this->honorService->handle($update, $message);
                //$successMessage = sprintf('saved "%s"', $message->getMessage());
                //$this->bot->sendMessage($chat->getChatId(), $successMessage, replyToMessageId: $update->getMessage()->getMessageId());
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $chatId = $update->getMessage()?->getChat()?->getId();
            if ($chatId) {
                $this->bot->sendMessage($chatId, 'sadge');
            }
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

}