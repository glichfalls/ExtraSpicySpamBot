<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class SlotMachineChatCommand extends AbstractTelegramChatCommand implements TelegramCallbackQueryListener
{

    private const PRICE = 10_000;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,

    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!slot\s*(?<amount>\d+)?$/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $jackpot = $this->honorService->getSlotMachineJackpot($message->getChat());
        $text = <<<TEXT
        🎰 SLOT MACHINE 🎰
        
        jackpot: %s Ehre
        TEXT;
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            sprintf(
                $text,
                NumberFormat::format($jackpot->getAmount()),
            ),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboard(),
        );
    }

    /**
     * @return array<int, int|string>
     */
    private function run(): array
    {
        $options = ['🍒', '🍋', '🍊', '🍇', '🍉', '🍓', '🍍', '🍌', 7, '🍎'];
        return [
            Random::arrayElement($options),
            Random::arrayElement($options),
            Random::arrayElement($options),
        ];
    }

    public function getCallbackKeyword(): string
    {
        return 'slot';
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $currentHonor = $this->honorService->getCurrentHonorAmount($chat, $user);
        if ($currentHonor < self::PRICE) {
            $this->telegramService->answerCallbackQuery($callbackQuery, 'not enough Ehre', true);
            return;
        }
        $jackpot = $this->honorService->getSlotMachineJackpot($chat);
        $this->honorService->removeHonor($chat, $user, self::PRICE);
        $jackpot->setAmount($jackpot->getAmount() + self::PRICE);
        $result = $this->run();
        if ($result === [7,7,7]) {
            $amount = $jackpot->getAmount();
            $this->honorService->addHonor($chat, $user, $amount);
            $text = <<<TEXT
                🎰 777 🎰
                
                JACKPOT
                @%s wins %s Ehre
                TEXT;
            $this->honorService->removeHonor($chat, $user, self::PRICE);
            $this->telegramService->sendText($chat->getChatId(), sprintf($text, $user->getName(), NumberFormat::format($amount)));
            $jackpot->setAmount(0);
            $this->manager->flush();
            $this->telegramService->answerCallbackQuery($callbackQuery);
            return;
        }
        $this->manager->flush();
        $this->telegramService->answerCallbackQuery(
            $callbackQuery,
            implode(' ', $result),
            true,
        );
    }

    public function getKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => sprintf('play (%s Ehre)', NumberFormat::format(self::PRICE)),
                    'callback_data' => 'slot'
                ],
            ],
        ]);
    }

    public function getDescription(): string
    {
        return 'play the slot machine';
    }

    public function getSyntax(): string
    {
        return '!slot [amount]';
    }

}