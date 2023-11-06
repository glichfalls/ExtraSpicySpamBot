<?php

namespace App\Service\Telegram\Honor\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemKeyword;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Service\Items\ItemEffectService;
use App\Service\Items\ItemService;
use App\Service\Items\ItemTradeService;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\Button\TelegramButton;
use App\Service\Telegram\Button\TelegramKeyboard;
use App\Service\Telegram\TelegramService;
use App\Service\UserService;
use App\Utils\Random;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ItemExecutionChatCommand extends AbstractTelegramCallbackQuery
{
    public const CALLBACK_KEYWORD = 'item:exec';

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly UserService $userService,
        private readonly ItemService $itemService,
        private readonly ItemTradeService $itemTradeService,
        private readonly ItemEffectService $itemEffectService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $argumentCount = $this->countCallbackDataParts($update);
            if ($argumentCount === 1) {
                $instance = $this->itemService->getInstance($this->getCallbackDataId($update));
                $this->itemService->validateItemExecution($instance, $user);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf('%s executed %s.', $user->getName(), $instance->getItem()->getName()),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
                    replyMarkup: $this->getActionKeyboard($instance),
                );
            } else {
                $arguments = $this->getCallbackDataParts($update, 2);
                $instance = $this->itemService->getInstance($arguments[1]);
                if ($instance->getOwner()->getId() !== $user->getId()) {
                    throw new \RuntimeException('You are not the owner of this item.');
                }
                switch (ItemKeyword::tryFrom($arguments[0])) {
                    case ItemKeyword::SEND_ITEM:
                        $users = $instance->getChat()->getUsers();
                        $this->telegramService->sendText(
                            $chat->getChatId(),
                            sprintf('Who should receive %s?', $instance->getItem()->getName()),
                            threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
                            replyMarkup: $this->getSendKeyboard($instance, $users),
                        );
                        break;
                    case ItemKeyword::RECEIVE_ITEM:
                        $receiver = $this->userService->getUserByTelegramId($arguments[2]);
                        if ($receiver === null) {
                            throw new \InvalidArgumentException('User not found.');
                        }
                        if ($this->receiveItem($instance, $receiver)) {
                            $this->telegramService->sendText(
                                $chat->getChatId(),
                                sprintf('challenge successful. %s received %s.', $user->getName(), $instance->getItem()->getName()),
                                threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
                            );
                        } else {
                            $this->telegramService->sendText(
                                $chat->getChatId(),
                                sprintf('challenge failed. %s did not receive %s.', $user->getName(), $instance->getItem()->getName()),
                                threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
                            );
                        }
                        $this->manager->flush();
                        $this->telegramService->deleteMessage(
                            $chat->getChatId(),
                            $update->getCallbackQuery()->getMessage()->getMessageId(),
                        );
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid callback data.');
                }
            }
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), $exception, true);
            return;
        }
    }

    private function receiveItem(ItemInstance $instance, User $user): bool
    {
        $instance->setPayloadValue('last_execution', (new \DateTime())->format('Y-m-d H:i:s'));
        if (Random::getPercentChance($this->getGiftLuck($instance))) {
            $this->itemTradeService->transferItem($instance, $user);
            return true;
        }
        return false;
    }

    private function getGiftLuck(ItemInstance $instance): int
    {
        $effects = $this->itemEffectService->getEffectsByUserAndType(
            $instance->getOwner(),
            $instance->getChat(),
            EffectType::LUCK
        );
        return $effects->apply(33);
    }

    private function getSendKeyboard(ItemInstance $instance, Collection $users): InlineKeyboardMarkup
    {
        return $this->telegramService->createKeyboard(new TelegramKeyboard(
            $users->map(
                fn (User $user): TelegramButton =>
                new TelegramButton($user->getName(), sprintf(
                    '%s:%s:%s:%s',
                    self::CALLBACK_KEYWORD,
                    ItemKeyword::RECEIVE_ITEM->value,
                    $instance->getId(),
                    $user->getTelegramUserId(),
                )),
            )->toArray()
        ));
    }

    private function getActionKeyboard(ItemInstance $instance): InlineKeyboardMarkup
    {
        return $this->telegramService->createKeyboard(new TelegramKeyboard([
            new TelegramButton(
                'Gi',
                sprintf(
                    '%s:%s:%s',
                    self::CALLBACK_KEYWORD,
                    ItemKeyword::SEND_ITEM->value,
                    $instance->getId()
                )
            ),
            new TelegramButton('Pay to remove (coming soon)')
        ]));
    }

}
