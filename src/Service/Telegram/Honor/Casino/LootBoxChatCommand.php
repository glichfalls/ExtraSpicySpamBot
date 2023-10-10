<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\Collectable;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Service\Telegram\Collectables\CollectableService;
use App\Service\Telegram\Honor\AbstractTelegramHonorChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class LootBoxChatCommand extends AbstractTelegramHonorChatCommand implements TelegramCallbackQueryListener
{

    public const CALLBACK_KEYWORD = 'lootbox';

    private const SMALL = 's';
    private const MEDIUM = 'm';
    private const LARGE = 'l';
    private const SIZES = [
        self::SMALL,
        self::MEDIUM,
        self::LARGE,
    ];

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        HonorRepository $honorRepository,
        private CollectableService $collectableService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService, $honorRepository);
    }

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
            try {
                $result = $this->getLootboxWin($chat, $user, $size);
            } catch (\RuntimeException) {
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    'you won a collectable, but theres no collectable left to win. You win the jackpot instead.',
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                );
                $result = 10_000_000;
            }
            if ($result instanceof CollectableItemInstance) {
                $this->telegramService->answerCallbackQuery($callbackQuery, 'You won a collectable', false);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf(
                        '%s won a <strong>%s</strong> collectable from a <strong>%s</strong> lootbox',
                        $user->getName() ?? $user->getFirstName(),
                        $result->getCollectable()->getName(),
                        ucfirst($size),
                    ),
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                    parseMode: 'HTML',
                );
                return;
            }
            $this->addHonor($chat, $user, $result);
            $this->manager->flush();
            $this->telegramService->answerCallbackQuery($callbackQuery, sprintf('You won %s honor', NumberFormat::format($result)), false);
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf(
                    '%s won %s Ehre from a <strong>%s</strong> lootbox',
                    $user->getName() ?? $user->getFirstName(),
                    NumberFormat::format($result),
                    ucfirst($size),
                ),
                threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                parseMode: 'HTML',
            );
        }
    }

    private function getLootboxWin(Chat $chat, User $user, string $size): int|CollectableItemInstance
    {
        $baseFailChance = match ($size) {
            self::SMALL => 75,
            self::MEDIUM => 60,
            self::LARGE => 50,
            default => 100,
        };
        if ($this->getPercentChance($baseFailChance)) {
            if ($this->getPercentChance(50)) {
                return $this->getPrice($size);
            }
            return (int) floor($this->getPrice($size) / $this->getNumber(5, 2));
        }
        if ($this->getPercentChance(50)) {
            return $this->getNumber($this->getPrice($size) * 5, $this->getPrice($size));
        }
        if ($this->getPercentChance(10)) {
            return $this->getNumber($this->getPrice($size) * 15, $this->getPrice($size) * 5);
        }
        if ($this->getPercentChance(1)) {
            if ($this->getPercentChance(10)) {
                return $this->getPrice($size) * 100;
            }
            return $this->getNumber($this->getPrice($size) * 99, $this->getPrice($size) * 10);
        }
        $collectableChance = match ($size) {
            self::SMALL => 25,
            self::MEDIUM => 50,
            self::LARGE => 75,
            default => 0,
        };
        if ($this->getPercentChance($collectableChance)) {
            return $this->winCollectable($chat, $user);
        }
        return match($size) {
            self::SMALL => 9001,
            self::MEDIUM => 69_420,
            self::LARGE => 420_420,
            default => 0,
        };
    }

    private function winCollectable(Chat $chat, User $user): CollectableItemInstance
    {
        $collectables = $this->collectableService->getAvailableInstances($chat);
        $win = $collectables[array_rand($collectables)];
        $win->setOwner($user);
        $this->manager->flush();
        return $win;
    }

    private function getPercentChance(int $probability): bool
    {
        return $this->getNumber(100) <= $probability;
    }

    private function getNumber(int $max, int $min = 1): int
    {
        return mt_rand($min, $max);
    }

    private function getKeyboard(): InlineKeyboardMarkup
    {
        $keyboard = [];
        foreach (self::SIZES as $size) {
            $price = $this->getPrice($size);
            if ($price < 1000) {
                $priceFormatted = sprintf('%.1fk', $this->getPrice($size) / 1000);
            } else {
                $format = $price >= 1_000_000 ? '%dm' : '%dk';
                $displayPrice = $price >= 1_000_000 ? $price / 1_000_000 : $price / 1000;
                $priceFormatted = sprintf($format, $displayPrice);
            }
            $keyboard[] = [
                'text' => sprintf('%s Ehre', $priceFormatted),
                'callback_data' => sprintf('%s;%s', self::CALLBACK_KEYWORD, $size),
            ];
        }
        return new InlineKeyboardMarkup([$keyboard]);
    }

    private function getPrice(string $size): ?int
    {
        return match ($size) {
            self::SMALL => 5_000,
            self::MEDIUM => 50_000,
            self::LARGE => 1_000_000,
            default => null,
        };
    }

}