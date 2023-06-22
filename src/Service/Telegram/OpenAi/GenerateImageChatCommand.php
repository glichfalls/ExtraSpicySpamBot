<?php

namespace App\Service\Telegram\OpenAi;

use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\GeneratedImageRepository;
use App\Service\OpenApi\OpenAiImageService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GenerateImageChatCommand extends AbstractTelegramChatCommand
{

    private const USER_RATE_LIMIT_SECONDS = 120;
    private const GENERAL_RATE_LIMIT_SECONDS = 30;

    private const SIZES = [
        's' => '256x256',
        'm' => '512x512',
        'l' => '1024x1024',
    ];

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
        return preg_match('/^aiimg\s(?<size>[sml])?\s?(?<prompt>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $secondsToWait = $this->getRateLimitSeconds($message->getUser());
            if ($secondsToWait > 0) {
                $this->telegramService->replyTo($message, $this->translator->trans('telegram.openai.rate_limit', [
                    'seconds' => $secondsToWait,
                ]));
            } else {
                $generatedImage = $this->openAiImageService->generateImage(
                    $message->getUser(),
                    $matches['prompt'],
                    $this->getSize($matches),
                );
                $this->telegramService->imageReplyTo(
                    $message,
                    sprintf('https://%s/%s', $_SERVER['HTTP_HOST'], $generatedImage->getPublicPath()),
                );
            }
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage());
            $this->telegramService->replyTo($message, $th->getMessage());
        }
    }

    private function getRateLimitSeconds(User $user): int
    {
        $latestGeneratedImage = $this->generatedImageRepository->getLatest();
        if ($latestGeneratedImage === null) {
            return false;
        }
        $diffSeconds = $this->intervalToSeconds($latestGeneratedImage->getCreatedAt()->diff(new \DateTime()));
        if ($diffSeconds < self::GENERAL_RATE_LIMIT_SECONDS) {
            return self::GENERAL_RATE_LIMIT_SECONDS - $diffSeconds;
        }
        $latestUserGeneratedImage = $this->generatedImageRepository->getLatestByUser($user);
        $diff = $this->intervalToSeconds($latestUserGeneratedImage?->getCreatedAt()->diff(new \DateTime()));
        return self::USER_RATE_LIMIT_SECONDS - $diff;
    }

    private function intervalToSeconds(\DateInterval $interval): int
    {
        return ($interval->d * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
    }

    private function getSize(array $matches): string
    {
        if (array_key_exists('size', $matches)) {
            $size = strtolower($matches['size']);
            if (array_key_exists($size, self::SIZES)) {
                return self::SIZES[$size];
            }
        }
        return self::SIZES['s'];
    }

}