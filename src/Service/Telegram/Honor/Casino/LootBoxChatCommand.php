<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\Honor\AbstractTelegramHonorChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class LootBoxChatCommand extends AbstractTelegramHonorChatCommand implements TelegramCallbackQueryListener
{

    public const CALLBACK_KEYWORD = 'lootbox';

    private const SMALL = 'small';
    private const MEDIUM = 'medium';
    private const LARGE = 'large';
    private const SIZES = [
        self::SMALL,
        self::MEDIUM,
        self::LARGE,
    ];

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!lootbox/i', $message->getMessage(), $matches) === 1;
    }

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            'Choose your lootbox size',
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboard()
        );
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $data = explode(';', $callbackQuery->getData());
        if (count($data) === 2) {
            $size = $data[1];
            $price = $this->getPrice($size);
            if ($price === null) {
                $this->telegramService->answerCallbackQuery($callbackQuery, 'Invalid size', false);
                return;
            }
            $currentHonor = $this->getCurrentHonorAmount($chat, $user);
            if ($currentHonor < $price) {
                $this->telegramService->answerCallbackQuery($callbackQuery, 'Not enough honor', true);
                return;
            }
            $this->removeHonor($chat, $user, $price);
            $result = $this->roll($size);
            $this->addHonor($chat, $user, $result);
            $this->telegramService->answerCallbackQuery($callbackQuery, sprintf('You won %s honor', NumberFormat::format($result)), false);
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf(
                    '%s won %s Ehre from a %s lootbox',
                    $user->getName() ?? $user->getFirstName(),
                    NumberFormat::format($result),
                    $size
                ),
                threadId: $callbackQuery->getMessage()->getMessageThreadId(),
            );
        }
    }

    private function roll(string $size)
    {
        $min = match ($size) {
            self::SMALL => 0,
            self::MEDIUM => 100,
            self::LARGE => 1000,
        };
        $max = match ($size) {
            self::SMALL => 100_000,
            self::MEDIUM => 1_000_000,
            self::LARGE => 100_000_000,
        };
        $numberOfIterations = match ($size) {
            self::SMALL => 10,
            self::MEDIUM => 20,
            self::LARGE => 40,
        };
        $results = [1];
        for ($i = 0; $i < $numberOfIterations; $i++) {
            $results[] = random_int($min, $max);
        }
        sort($results);
        $lowest = array_shift($results);
        $highest = array_pop($results);
        $results = match ($size) {
            self::SMALL => array_slice($results, 2, 5),
            self::MEDIUM => array_slice($results, 4, 10),
            self::LARGE => array_slice($results, 8, 20),
        };
        $results[] = $lowest;
        $results[] = $highest;
        $win = array_sum($results) / count($results);
        return max($win, $min);
    }

    private function getKeyboard(): InlineKeyboardMarkup
    {
        $keyboard = [];
        foreach (self::SIZES as $size) {
            $keyboard[] = [
                'text' => sprintf('%s (%s Ehre)', ucfirst($size), NumberFormat::format($this->getPrice($size))),
                'callback_data' => sprintf('%s;%s', self::CALLBACK_KEYWORD, $size),
            ];
        }
        return new InlineKeyboardMarkup([$keyboard]);
    }

    private function getPrice(string $size): ?int
    {
        return match ($size) {
            self::SMALL => 1000,
            self::MEDIUM => 5000,
            self::LARGE => 10000,
            default => null,
        };
    }

}