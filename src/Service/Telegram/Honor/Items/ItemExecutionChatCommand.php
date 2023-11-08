<?php

namespace App\Service\Telegram\Honor\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemKeyword;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Service\HonorService;
use App\Service\Items\ItemEffectService;
use App\Service\Items\ItemService;
use App\Service\Items\ItemTradeService;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\Button\TelegramButton;
use App\Service\Telegram\Button\TelegramKeyboard;
use App\Service\Telegram\TelegramService;
use App\Service\UserService;
use App\Utils\NumberFormat;
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
        private readonly HonorService $honorService,
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
            if ($this->countCallbackDataParts($update) === 1) {
                $instance = $this->itemService->getInstance($this->getCallbackDataId($update));
                $this->itemService->validateItemExecution($instance, $user);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf('%s executed %s.', $user->getName(), $instance->getItem()->getName()),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                    replyMarkup: $this->getActionKeyboard($instance),
                );
                $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
            } else {
                $arguments = $this->getCallbackDataParts($update, [2, 3]);
                $instance = $this->itemService->getInstance($arguments[1]);
                if ($instance->getOwner()->getId() !== $user->getId()) {
                    throw new \RuntimeException('You are not the owner of this item.');
                }
                $this->itemService->validateItemExecution($instance, $user);
                switch (ItemKeyword::tryFrom($arguments[0])) {
                    case ItemKeyword::SEND_ITEM:
                        $users = $instance->getChat()->getUsers();
                        $this->telegramService->sendText(
                            $chat->getChatId(),
                            sprintf('Who should receive %s?', $instance->getItem()->getName()),
                            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                            replyMarkup: $this->getSendKeyboard($instance, $users),
                        );
                        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
                        break;
                    case ItemKeyword::RECEIVE_ITEM:
                        $id = (int) $arguments[2];
                        if ($id === 0) {
                            throw new \InvalidArgumentException('Invalid item instance id.');
                        }
                        $recipient = $this->userService->getUserByTelegramId($id);
                        if ($recipient === null) {
                            throw new \InvalidArgumentException('User not found.');
                        }
                        if ($this->receiveItem($instance, $recipient)) {
                            $this->telegramService->sendText(
                                $chat->getChatId(),
                                sprintf('challenge successful. %s received %s.', $recipient->getName(), $instance->getItem()->getName()),
                                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                            );
                        } else {
                            $this->telegramService->sendText(
                                $chat->getChatId(),
                                sprintf('challenge failed. %s did not receive %s.', $recipient->getName(), $instance->getItem()->getName()),
                                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                            );
                        }
                        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
                        $this->manager->flush();
                        break;
                    case ItemKeyword::PAY_ITEM:
                        $price = $instance->getItem()->getPrice();
                        if ($price === null) {
                            throw new \RuntimeException('This item cannot be paid.');
                        }
                        $honor = $this->honorService->getCurrentHonorAmount($chat, $user);
                        if ($honor < $price) {
                            throw new \RuntimeException('You do not have enough Ehre.');
                        }
                        $this->honorService->removeHonor($chat, $user, $price);
                        $this->itemTradeService->transferItem($instance);
                        $this->telegramService->sendText(
                            $chat->getChatId(),
                            sprintf('%s paid %s Ehre to remove %s.', $user->getName(), NumberFormat::format($price), $instance->getItem()->getName()),
                            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                        );
                        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid callback data.');
                }
                $this->telegramService->deleteMessage(
                    $chat->getChatId(),
                    $update->getCallbackQuery()->getMessage()->getMessageId(),
                );
            }
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), $exception->getMessage(), true);
            return;
        } catch (\Throwable $exception) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Something went wrong.', true);
            $this->logger->error($exception->getMessage());
            throw new \RuntimeException($exception->getMessage(), previous: $exception);
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
            $users->filter(fn (User $user) => $user->getName() !== null)->map(
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
                'send to a friend (33%)',
                sprintf(
                    '%s:%s:%s',
                    self::CALLBACK_KEYWORD,
                    ItemKeyword::SEND_ITEM->value,
                    $instance->getId()
                )
            ),
            new TelegramButton(
                sprintf('pay %s Ehre (100%%)', NumberFormat::format($instance->getItem()->getPrice())),
                sprintf(
                    '%s:%s:%s',
                    self::CALLBACK_KEYWORD,
                    ItemKeyword::PAY_ITEM->value,
                    $instance->getId()
                )
            ),
        ]));
    }

}
