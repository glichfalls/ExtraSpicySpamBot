<?php

namespace App\Service\Telegram;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use App\Entity\Message\Message;
use App\Entity\Message\MessageFactory;
use App\Entity\Sticker\Sticker;
use App\Entity\Sticker\StickerFactory;
use App\Entity\Sticker\StickerFile;
use App\Entity\Sticker\StickerFileFactory;
use App\Entity\Sticker\StickerSet;
use App\Entity\Sticker\StickerSetFactory;
use App\Entity\User\User;
use App\Entity\User\UserFactory;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Repository\StickerFileRepository;
use App\Repository\StickerSetRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaVideo;
use TelegramBot\Api\Types\Message as TelegramMessage;
use TelegramBot\Api\Types\MessageEntity;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;
use Twig\Environment;

class TelegramService
{

    public function __construct(
        private KernelInterface $kernel,
        protected BotApi $bot,
        protected LoggerInterface $logger,
        protected Environment $twig,
        protected UserService $userService,
        protected EntityManagerInterface $manager,
        protected ChatRepository $chatRepository,
        protected MessageRepository $messageRepository,
        protected UserRepository $userRepository,
        protected StickerFileRepository $stickerFileRepository,
        protected StickerSetRepository $stickerSetRepository,
    )
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            $this->bot->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        }
    }

    /**
     * @param Update $update
     * @return array<User>
     */
    public function getUsersFromMentions(Update $update): array
    {
        $users = [];
        /** @var MessageEntity $entity */
        foreach ($update->getMessage()->getEntities() as $entity) {
            $user = match ($entity->getType()) {
                MessageEntity::TYPE_MENTION => $this->userService->getByName(
                    substr(
                        $update->getMessage()->getText(),
                        $entity->getOffset() + 1,
                        $entity->getLength() - 1
                    )
                ),
                MessageEntity::TYPE_TEXT_MENTION => $this->userService->createUserFromMessageEntity($entity),
                default => null,
            };
            if ($user !== null) {
                $this->logger->info(sprintf('Found mention %s', $entity->toJson()));
                $users[] = $user;
            }
        }
        return $users;
    }

    public function sendVideo(string $chatId, string $url): ?array
    {
        $media = new ArrayOfInputMedia();
        $media->addItem(new InputMediaVideo($url));
        return $this->bot->sendMediaGroup($chatId, $media);
    }

    public function sendText(string $chatId, string $text, ?int $threadId = null): ?TelegramMessage
    {
        return $this->bot->sendMessage($chatId, $text, messageThreadId: $threadId);
    }

    public function stickerReplyTo(Message $message, Sticker $sticker): TelegramMessage
    {
        return $this->bot->sendSticker(
            $message->getChat()->getChatId(),
            $sticker->getFile()->getFileId(),
            replyToMessageId: $message->getTelegramMessageId(),
        );
    }

    public function replyTo(
        Message $message,
        string $text,
        ?ReplyKeyboardMarkup $replyMarkup = null,
        $parseMode = null,
    ): TelegramMessage
    {
        return $this->bot->sendMessage(
            $message->getChat()->getChatId(),
            $text,
            parseMode: $parseMode,
            replyToMessageId: $message->getTelegramMessageId(),
            replyMarkup: $replyMarkup,
        );
    }

    public function videoReplyTo(
        Message $message,
        string $videoUrl,
    ): TelegramMessage
    {
        return $this->bot->sendVideo(
            $message->getChat()->getChatId(),
            $videoUrl,
            replyToMessageId: $message->getTelegramMessageId(),
        );
    }

    public function imageReplyTo(Message $message, string $imageUrl): TelegramMessage
    {
        return $this->bot->sendPhoto(
            $message->getChat()->getChatId(),
            $imageUrl,
            replyToMessageId: $message->getTelegramMessageId(),
        );
    }

    public function createStickerSet(User $owner, string $name, string $title, string $emoji, string $stickerPath): ?StickerSet
    {
        try {
            $set = $this->stickerSetRepository->getByTitleOrNull($title);
            if ($set !== null) {
                $this->logger->warning(sprintf('Sticker set %s already exists', $name));
                return null;
            }
            $stickerFile = $this->uploadStickerFile($owner, $stickerPath);
            if ($stickerFile === null) {
                $this->logger->warning(sprintf('failed to upload sticker file %s', $stickerPath));
                return null;
            }
            $set = StickerSetFactory::create($owner, $name, $title);
            $data = $this->bot->call('createNewStickerSet', [
                'user_id' => $owner->getTelegramUserId(),
                'name' => $set->getName(),
                'title' => $set->getTitle(),
                'stickers' => json_encode([
                    ['sticker' => $stickerFile->getFileId(), 'emoji_list' => [$emoji]],
                ]),
                'sticker_format' => $set->getStickerFormat(),
            ]);
            if ($data === true) {
                $this->manager->persist($set);
                $this->manager->flush();
                return $set;
            }
            $this->logger->warning(sprintf('failed to create sticker set %s', $name));
            return null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function addStickerToSet(StickerSet $set, string $stickerPath, array $emojiList): ?Sticker
    {
        try {
            $stickerFile = $this->uploadStickerFile($set->getOwner(), $stickerPath);
            if ($stickerFile === null) {
                return null;
            }
            $sticker = StickerFactory::create($set, $stickerFile, $emojiList);
            $data = $this->bot->call('addStickerToSet', [
                'user_id' => $sticker->getStickerSet()->getOwner()->getTelegramUserId(),
                'name' => $sticker->getStickerSet()->getName(),
                'sticker' => json_encode([
                   'sticker' => $sticker->getFile()->getFileId(),
                   'emoji_list' => $sticker->getEmojis(),
                ]),
            ]);
            if ($data === true) {
                $this->manager->persist($sticker);
                $this->manager->flush();
                return $sticker;
            }
            return null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    private function uploadStickerFile(User $user, string $stickerPath): ?StickerFile
    {
        try {
            $existingStickerFile = $this->stickerFileRepository->getBySticker($stickerPath);
            if ($existingStickerFile !== null) {
                return $existingStickerFile;
            }
            $serverPath = sprintf('%s/public/%s', $this->kernel->getProjectDir(), $stickerPath);
            if (!file_exists($serverPath)) {
                $this->logger->error(sprintf('File %s does not exist', $stickerPath));
                return null;
            }
            // workaround for size limit by telegram (which should actually be 5MB, but is lower than 700KB)
            $im = imagecreatefrompng($serverPath);
            imagepng($im, $serverPath, 5);
            imagedestroy($im);
            $stickerFile = StickerFileFactory::create($user, $serverPath);
            $this->manager->persist($stickerFile);
            $this->manager->flush();
            $data = $this->bot->call('uploadStickerFile', [
                'user_id' => $stickerFile->getOwner()->getTelegramUserId(),
                'sticker' => new \CURLFile($stickerFile->getSticker()),
                'sticker_format' => $stickerFile->getStickerFormat(),
            ]);
            $stickerFile->setFileId($data['file_id']);
            $stickerFile->setFileUniqueId($data['file_unique_id']);
            $stickerFile->setFileSize($data['file_size']);
            $stickerFile->setFilePath($data['file_path'] ?? null);
            $this->manager->flush();
            return $stickerFile;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function createMessageFromUpdate(Update $update): Message
    {
        if (!$update->getMessage()?->getText()) {
            throw new \RuntimeException('message not found');
        }
        $chat = $this->getChatFromUpdate($update);
        $sender = $this->getSenderFromUpdate($update);
        if (!$chat || !$sender) {
            throw new \RuntimeException('chat or sender not found');
        }
        $message = MessageFactory::create($chat, $sender, $update->getMessage());
        $this->manager->persist($message);
        $this->manager->flush();
        return $message;
    }

    public function renderMessage(string $template, array $context = []): string
    {
        try {
            $message = $this->twig->render(sprintf('telegram/messages/%s.html.twig', $template), $context);
            return str_replace('<br>', PHP_EOL, $message);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return 'failed to render message';
        }
    }

    public function renderReplyTo(Message $message, string $template, array $context = []): void
    {
        $this->replyTo($message, $this->renderMessage($template, $context), parseMode: 'HTML');
    }

    private function getChatFromUpdate(Update $update): ?Chat
    {
        if ($update->getMessage()?->getChat()?->getId() === null) {
            return null;
        }
        $chat = $this->chatRepository->getChatByTelegramId($update->getMessage()->getChat()->getId());
        if (!$chat) {
            $chat = ChatFactory::createFromUpdate($update);
            $this->manager->persist($chat);
            $this->manager->flush();
        }
        return $chat;
    }

    private function getSenderFromUpdate(Update $update): ?User
    {
        if ($update->getMessage()?->getFrom()?->getId() === null) {
            return null;
        }
        /** @var User|null $user */
        $user = $this->userRepository->getByTelegramId($update->getMessage()->getFrom()->getId());
        if (!$user) {
            $user = UserFactory::createFromTelegramUser($update->getMessage()->getFrom());
            $this->manager->persist($user);
            return $user;
        }
        if ($user->getName() !== $update->getMessage()->getFrom()->getUsername()) {
            $user->setName($update->getMessage()->getFrom()->getUsername());
            $this->manager->flush();
        }
        if ($user->getFirstName() !== $update->getMessage()->getFrom()->getFirstName()) {
            $user->setFirstName($update->getMessage()->getFrom()->getFirstName());
            $this->manager->flush();
        }
        if ($user->getLastName() !== $update->getMessage()->getFrom()->getLastName()) {
            $user->setLastName($update->getMessage()->getFrom()->getLastName());
            $this->manager->flush();
        }
        return $user;
    }

}