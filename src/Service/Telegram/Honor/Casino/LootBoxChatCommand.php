<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\ItemFactory;
use App\Entity\Item\ItemInstance;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Service\Items\ItemEffectService;
use App\Service\Items\ItemService;
use App\Service\Telegram\Button\TelegramButton;
use App\Service\Telegram\Button\TelegramKeyboard;
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
        $text = ['Choose your lootbox size:'];
        foreach (LootboxLoot::cases() as $loot) {
            $text[] = sprintf(
                '%s %s-%s %sx loot',
                $loot->value,
                $loot->minRarity()->emoji(),
                $loot->maxRarity()->emoji(),
                $loot->base(),
            );
        }
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            implode(PHP_EOL, $text),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboard()
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
            $currentHonor = $this->getCurrentHonorAmount($chat, $user);
            if ($currentHonor < $loot->price()) {
                $this->telegramService->answerCallbackQuery($callbackQuery, 'Not enough honor', true);
                return;
            }
            $this->removeHonor($chat, $user, $loot->price());
            try {
                $result = $this->getLootboxWin($chat, $user, $loot);
                if ($result === 0) {
                    $result = $this->getRandomJunk();
                    $this->telegramService->answerCallbackQuery(
                        $callbackQuery,
                        sprintf('You won %s', $result),
                        true
                    );
                    $this->manager->flush();
                    return;
                }
            } catch (\RuntimeException) {
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    'F',
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                );
                $this->addHonor($chat, $user, $loot->price());
                return;
            }
            if ($result instanceof ItemInstance) {
                $message = sprintf('You won %s', $result->getItem()->getFullName());
                if ($result->getExpiresAt() !== null) {
                    $message .= sprintf(' (expires in %s)', $result->getExpiresAt()->diff(new \DateTime())->format('%a days'));
                }
                $this->telegramService->answerCallbackQuery($callbackQuery, $message, true);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf(
                        '%s won a <strong>%s</strong> from a <strong>%s</strong> lootbox',
                        $user->getName() ?? $user->getFirstName(),
                        $result->getItem()->getName(),
                        $loot->value,
                    ),
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                    parseMode: 'HTML',
                );
                return;
            }
            $this->addHonor($chat, $user, $result);
            $this->manager->flush();
            if ($result > ($loot->price() * 5)) {
                $this->telegramService->answerCallbackQuery($callbackQuery);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf(
                        '%s won %s Ehre from a <strong>%s</strong> lootbox',
                        $user->getName() ?? $user->getFirstName(),
                        NumberFormat::format($result),
                        $loot->value,
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

    private function getLootboxWin(Chat $chat, User $user, LootboxLoot $lootbox): int|ItemInstance
    {
        $effects = $this->itemEffectService->getEffectsByUserAndType($user, $chat, [
            EffectType::LOOTBOX_LUCK,
            EffectType::LUCK,
        ]);
        // nothing
        if (Random::getPercentChance($lootbox->junkRate($effects))) {
            return 0;
        }
        // bad loot
        if (Random::getPercentChance($lootbox->badLootRate($effects))) {
            return (int) floor($lootbox->price() / Random::getNumber(30, 10));
        }
        // high ehre loot
        if (Random::getPercentChance($lootbox->honorLootRate($effects))) {
            $max = $lootbox->price() * Random::getNumber(Random::getNumber(50, 2));
            // win between 200% of price and max
            return Random::getNumber($max, $lootbox->price() * 2);
        }
        if (Random::getPercentChance($lootbox->itemLootRate($effects))) {
            return $this->winItem($chat, $user, $lootbox);
        }
        return $lootbox->price() - 1;
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
            $item = $this->itemService->getRandomItemByRarity($rarity);
            $expire = new \DateTime('+2 weeks');
            $win = ItemFactory::instance($item, $chat, $user, true, $expire);
            $this->manager->persist($win);
        } else {
            $win = $instances[array_rand($instances->getValues())];
        }
        $win->setOwner($user);
        $win->setPayloadValue('lootbox', true);
        $this->manager->flush();
        return $win;
    }

    private function getKeyboard(): InlineKeyboardMarkup
    {
        $keyboard = new TelegramKeyboard([]);
        foreach (LootboxLoot::cases() as $loot) {
            $keyboard->add(new TelegramButton(
                sprintf('%s (%s Ehre)', $loot->value, NumberFormat::format($loot->price())),
                sprintf('%s;%s', self::CALLBACK_KEYWORD, $loot->value),
            ));
        }
        return $this->telegramService->createKeyboard($keyboard);
    }

}
