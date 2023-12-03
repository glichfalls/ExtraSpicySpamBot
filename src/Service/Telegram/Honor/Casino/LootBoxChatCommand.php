<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\ItemFactory;
use App\Entity\Item\ItemInstance;
use App\Entity\Message\Message;
use App\Entity\Stocks\Transaction\StockTransaction;
use App\Entity\Stocks\Transaction\StockTransactionFactory;
use App\Entity\User\User;
use App\Service\Honor\HonorService;
use App\Service\Items\ItemEffectService;
use App\Service\Items\ItemService;
use App\Service\Stocks\StockPriceService;
use App\Service\Stocks\StockService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\Button\TelegramButton;
use App\Service\Telegram\Button\TelegramKeyboard;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class LootBoxChatCommand extends AbstractTelegramChatCommand implements TelegramCallbackQueryListener
{

    public const CALLBACK_KEYWORD = 'lootbox';
    private const STOCK_LIST = [
        'MSFT',
        'AAPL',
        'TSLA',
        'AMZN',
        'GOOG',
        'META',
        'NVDA',
        'AMD',
        'BRK.B',
        'MMM',
        'ORCL',
    ];
    private const EXTENDED_LIST = [
        'BRK.A',
        'PLTR',
    ];

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
        private readonly ItemService $itemService,
        private readonly ItemEffectService $itemEffectService,
        private readonly StockService $stockService,
        private readonly StockPriceService $stockPriceService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
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
        $text = ['Choose your lootbox size:'];
        foreach (LootboxLoot::cases() as $loot) {
            $template = <<<TEMPLATE
            *{$loot->value}* | *%s* Ehre
            {$loot->base()}x loot chance (min {$loot->maxDebuff()} / max {$loot->maxBuff()})
            {$loot->junkRate(null)}%% junk
            {$loot->itemRate(null)}%% item (min {$loot->minRarity()->emoji()} / max {$loot->maxRarity()->emoji()})
            {$loot->stockRate(null)}%% (*%s* - *%s* stocks)
            TEMPLATE;
            $text[] = sprintf(
                $template,
                NumberFormat::money($loot->price()),
                NumberFormat::format($loot->minStockAmount()),
                NumberFormat::format($loot->maxStockAmount()),
            );
        }
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            implode(sprintf('%s----%s', PHP_EOL, PHP_EOL), $text),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboard(),
            parseMode: 'Markdown'
        );
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $data = explode(';', $callbackQuery->getData());
        if (count($data) === 2) {
            $loot = LootboxLoot::tryFrom($data[1]);
            if ($loot === null) {
                $this->telegramService->answerCallbackQuery($callbackQuery, 'Invalid size', true);
                return;
            }
            $currentHonor = $this->honorService->getCurrentHonorAmount($chat, $user);
            if ($currentHonor < $loot->price()) {
                $this->telegramService->answerCallbackQuery($callbackQuery, 'Not enough honor', true);
                return;
            }
            $this->honorService->removeHonor($chat, $user, $loot->price());
            $effects = $this->itemEffectService->getEffectsByUserAndType($user, $chat, [
                EffectType::LOOTBOX_LUCK,
                EffectType::LUCK,
            ]);
            $result = $this->getLootboxWin($chat, $user, $loot, $effects);
            if ($result === 0) {
                $this->telegramService->answerCallbackQuery($callbackQuery);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf(
                        '@%s won %s from a *%s* lootbox (%s%%)',
                        $user->getName() ?? $user->getFirstName(),
                        $this->getRandomJunk(),
                        $loot->value,
                        $loot->junkRate($effects),
                    ),
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                    parseMode: 'markdown',
                );
            } elseif ($result instanceof ItemInstance) {
                $message = sprintf(
                    '@%s won *%s*',
                    $user->getName() ?? $user->getFirstName(),
                    $result->getItem()->getFullName()
                );
                if ($result->getExpiresAt() !== null) {
                    $message .= sprintf(' (expires in %s)', $result->getExpiresAt()->diff(new \DateTime())->format('%a days'));
                }
                $message .= sprintf(' from a *%s* lootbox (%s%%)', $loot->value, $loot->itemRate($effects));
                $this->telegramService->answerCallbackQuery($callbackQuery);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    $message,
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                    parseMode: 'markdown',
                );
            } elseif ($result instanceof StockTransaction) {
                $this->telegramService->answerCallbackQuery($callbackQuery);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf(
                        '@%s won *%s* %s stocks (*%s* Ehre) from a *%s* lootbox (%s%%)',
                        $user->getName() ?? $user->getFirstName(),
                        NumberFormat::format($result->getAmount()),
                        $result->getPrice()->getStock()->getSymbol(),
                        NumberFormat::money($result->getHonorTotal()),
                        $loot->value,
                        $loot->stockRate($effects),
                    ),
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                    parseMode: 'markdown',
                );
            }
            $this->manager->flush();
        }
    }

    private function getLootboxWin(Chat $chat, User $user, LootboxLoot $lootbox, ?EffectCollection $effects): int|ItemInstance|StockTransaction
    {
        if (Random::getPercentChance($lootbox->junkRate($effects))) {
            return 0;
        }
        if (Random::getPercentChance($lootbox->itemRate($effects))) {
            return $this->winItem($chat, $user, $lootbox);
        } else {
            return $this->winStocks($chat, $user, $lootbox);
        }
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

    private function winItem(Chat $chat, User $user, LootboxLoot $loot): ItemInstance
    {
        $rarity = ItemRarity::random(
            $this->itemEffectService->getEffectsByUserAndType($user, $chat, [
                EffectType::LUCK,
            ]),
            maxRarity: $loot->maxRarity(),
            minRarity: $loot->minRarity(),
        );
        $instances = $this->itemService->getAvailableInstances($chat, $rarity);
        if ($instances->count() === 0) {
            $item = $this->itemService->getRandomLoanedItemByMaxRarity($rarity);
            $win = $this->itemService->createInstance($item, $chat, $user, expiresAt: new \DateTime('+2 weeks'));
        } else {
            $win = Random::arrayElement($instances->toArray());
        }
        $win->setOwner($user);
        $win->setPayloadValue('lootbox', true);
        $this->manager->flush();
        return $win;
    }

    private function winStocks(Chat $chat, User $user, LootboxLoot $loot): StockTransaction
    {
        $transaction = $this->stockService->createStockTransaction(
            $this->stockService->getPortfolioByChatAndUser($chat, $user),
            $this->getRandomStockSymbol(),
            $loot->stockAmount()
        );
        $this->manager->flush();
        return $transaction;
    }

    private function getRandomStockSymbol(): string
    {
        $options = self::STOCK_LIST;
        if (Random::getPercentChance(25)) {
            $options = self::EXTENDED_LIST;
            for ($i = 0; $i < 5; $i++) {
                // add the base list multiple times to decrease the chance of the extended list
                $options = array_merge($options, self::STOCK_LIST);
            }
        }
        return Random::arrayElement($options);
    }

    private function getKeyboard(): InlineKeyboardMarkup
    {
        $keyboard = new TelegramKeyboard([]);
        foreach (LootboxLoot::cases() as $loot) {
            $keyboard->add(new TelegramButton(
                sprintf('%s', $loot->value),
                sprintf('%s;%s', self::CALLBACK_KEYWORD, $loot->value),
            ));
        }
        return $this->telegramService->createKeyboard($keyboard);
    }

}
