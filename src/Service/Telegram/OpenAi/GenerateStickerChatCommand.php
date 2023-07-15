<?php

namespace App\Service\Telegram\OpenAi;

use App\Entity\Message\Message;
use App\Repository\StickerSetRepository;
use App\Repository\UserRepository;
use App\Service\OpenApi\OpenAiImageService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GenerateStickerChatCommand extends AbstractTelegramChatCommand
{
    private const STICKER_SET_NAME = 'extra_spicy_spam';
    private const STICKER_SET_TITLE = 'Extra Spicy Spam';
    private const OWNER_TELEGRAM_ID = 1098121923;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private UserRepository $userRepository,
        private StickerSetRepository $stickerSetRepository,
        private OpenAiImageService $openAiImageService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^aisticker (?<emoji>\X+) (?<prompt>.+)$/ui', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $emoji = $matches['emoji'];
        $prompt = $matches['prompt'];
        $owner = $this->userRepository->getByTelegramId(self::OWNER_TELEGRAM_ID);
        $stickerSet = $this->stickerSetRepository->getByTitleOrNull(self::STICKER_SET_TITLE);
        try {
            $image = $this->openAiImageService->generateImage($message->getUser(), $prompt, '512x512');
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, sprintf('failed to generate image: %s', $exception->getPrevious()?->getMessage()));
            return;
        }
        if ($stickerSet === null) {
            $set = $this->telegramService->createStickerSet(
                $owner,
                self::STICKER_SET_NAME,
                self::STICKER_SET_TITLE,
                $emoji,
                $image->getPublicPath(),
            );
            if ($set !== null) {
                $this->telegramService->replyTo($message, $emoji);
            } else {
                $this->telegramService->replyTo($message, 'Failed to create sticker set');
            }
        } else {
            $sticker = $this->telegramService->addStickerToSet($stickerSet, $image->getPublicPath(), [$emoji]);
            if ($sticker !== null) {
                $this->telegramService->stickerReplyTo($message, $sticker);
            } else {
                $this->telegramService->replyTo($message, 'Failed to add sticker to set');
            }
        }
    }

    public function getHelp(): string
    {
        return 'aisticker <emoji> <prompt>   generates a sticker with the given prompt and emoji';
    }

}