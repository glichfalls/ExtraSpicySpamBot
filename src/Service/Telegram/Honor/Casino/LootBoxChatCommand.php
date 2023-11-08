<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\ItemInstance;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Service\Items\ItemEffectService;
use App\Service\Items\ItemService;
use App\Service\Telegram\Honor\AbstractTelegramHonorChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
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
    private const XL = 'xl';
    private const XXL = 'xxl';
    private const SIZES = [
        self::SMALL,
        self::MEDIUM,
        self::LARGE,
        self::XL,
        self::XXL,
    ];

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        HonorRepository $honorRepository,
        private readonly ItemService $itemService,
        private readonly ItemEffectService $itemEffectService,
    ) {
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
                $this->telegramService->answerCallbackQuery($callbackQuery, 'Invalid size', true);
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
                if ($result === 0) {
                    $result = $this->getRandomJunk();
                    $this->telegramService->answerCallbackQuery($callbackQuery, sprintf('You won %s', $result), true);
                    $this->manager->flush();
                    return;
                }
            } catch (\RuntimeException) {
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    'F',
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                );
                $this->addHonor($chat, $user, $price * 30);
                return;
            }
            if ($result instanceof ItemInstance) {
                $this->telegramService->answerCallbackQuery($callbackQuery, 'You win a item', true);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf(
                        '%s won a <strong>%s</strong> from a <strong>%s</strong> lootbox',
                        $user->getName() ?? $user->getFirstName(),
                        $result->getItem()->getName(),
                        ucfirst($size),
                    ),
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                    parseMode: 'HTML',
                );
                return;
            }
            $this->addHonor($chat, $user, $result);
            $this->manager->flush();
            if ($result > $price * 4) {
                $this->telegramService->answerCallbackQuery($callbackQuery);
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
            } else {
                $this->telegramService->answerCallbackQuery(
                    $callbackQuery,
                    sprintf('You win %s Ehre', NumberFormat::format($result)),
                    true
                );
            }
        }
    }

    private function getLootboxWin(Chat $chat, User $user, string $size): int|ItemInstance
    {
        $hardFailChance = $this->itemEffectService->getEffectsByUserAndType($user, $chat, [
            EffectType::LOOTBOX_LUCK,
            EffectType::LUCK,
        ]);
        // nothing
        if (Random::getPercentChance(match($size) {
            self::SMALL => floor(20 / $hardFailChance->apply(1)),
            self::MEDIUM => floor(15 / $hardFailChance->apply(1)),
            self::LARGE => floor(10 / $hardFailChance->apply(1)),
            self::XL => floor(5 / $hardFailChance->apply(1)),
            self::XXL => floor(4 / $hardFailChance->apply(1)),
            default => 100,
        })) {
            return 0;
        }
        // -ehre loot
        if (Random::getPercentChance(match ($size) {
            self::SMALL => 59,
            self::MEDIUM => 58,
            self::LARGE => 57,
            self::XL => 56,
            self::XXL => 55,
            default => 100,
        })) {
            return (int) floor($this->getPrice($size) / Random::getNumber(8));
        }
        // medium ehre loot
        if (Random::getPercentChance(match ($size) {
            self::SMALL, self::MEDIUM => 90,
            self::LARGE, self::XL, self::XXL => 85,
            default => 0,
        })) {
            // win between 100% and 200% of price
            return Random::getNumber($this->getPrice($size) * 2, $this->getPrice($size));
        }
        // high ehre loot
        if (Random::getPercentChance(50)) {
            // max = 2-50x price
            $max = $this->getPrice($size) * Random::getNumber(Random::getNumber(50, 2));
            // win between 200% of price and max
            return Random::getNumber($max, $this->getPrice($size) * 2);
        }
        // collectable loot
        $effects = $this->itemEffectService->getEffectsByUserAndType($user, $chat, [
            EffectType::LUCK,
        ]);
        if (Random::getPercentChance($effects->apply(match ($size) {
            self::SMALL => 1,
            self::MEDIUM => 3,
            self::LARGE => 10,
            self::XL => 15,
            self::XXL => 20,
            default => 0,
        }))) {
            return $this->winItem($chat, $user);
        }
        return $this->getPrice($size) + 1;
    }

    private function getRandomJunk(): string
    {
        $junk = [
            'nothing',
            'a fake nft',
            'monopoly money',
        ];
        return $junk[array_rand($junk)];
    }

    private function winItem(Chat $chat, User $user): ItemInstance
    {
        $instances = $this->itemService->getAvailableInstances($chat, ItemRarity::random());
        if ($instances->count() === 0) {
            throw new \RuntimeException('No items available.');
        }
        $win = $instances[array_rand($instances->getValues())];
        $win->setOwner($user);
        $this->manager->flush();
        return $win;
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
            self::SMALL => 10_000,
            self::MEDIUM => 100_000,
            self::LARGE => 1_000_000,
            self::XL => 100_000_000,
            self::XXL => 1_000_000_000,
            default => null,
        };
    }

}
