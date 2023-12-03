<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Honor\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
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

        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            $this->getStartText($jackpot->getAmount()),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboard(),
        );
    }

    private function getStartText(Money $jackpot): string
    {
        $text = <<<TEXT
        🎰 SLOT MACHINE 🎰
        
        jackpot: %s Ehre
        TEXT;
        return sprintf($text, NumberFormat::money($jackpot));
    }

    /**
     * @return array<int, int|string>
     */
    private function run(): array
    {
        if (Random::number(1000) === 1) {
            return ['✡️', '✡️', '✡️'];
        }
        $options = ['🍒', '🍋', '🍊', '🍇', '🍉', '🍓', '🍍', '🍌', '💰', '🍎'];
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
        if ($currentHonor->lessThan(Honor::currency(self::PRICE))) {
            $this->telegramService->answerCallbackQuery($callbackQuery, 'not enough Ehre', true);
            return;
        }
        $jackpot = $this->honorService->getSlotMachineJackpot($chat);
        $this->honorService->removeHonor($chat, $user, Honor::currency(self::PRICE));
        $previousJackpot = $jackpot->getAmount();
        $jackpot->setAmount($previousJackpot->add(Honor::currency(self::PRICE)));
        $result = $this->run();
        if ($result === ['💰', '💰', '💰']) {
            $amount = $jackpot->getAmount();
            $this->honorService->addHonor($chat, $user, $amount);
            $text = <<<TEXT
                🎰 💰💰💰 🎰
                
                JACKPOT
                @%s wins %s Ehre
                TEXT;
            $this->honorService->removeHonor($chat, $user, Honor::currency(self::PRICE));
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf($text, $user->getName(), NumberFormat::money($amount)),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
            $jackpot->setAmount(Honor::currency(0));
            $this->manager->flush();
            $this->telegramService->answerCallbackQuery($callbackQuery);
            $this->updateJackpot($update, $previousJackpot, $jackpot->getAmount());
            return;
        }
        if ($result === ['✡️', '✡️', '✡️']) {
            $amount = Honor::currency(1_000_000);
            $this->honorService->removeHonor($chat, $user, $amount);
            $text = <<<TEXT
            🎰 ✡️ ✡️ ✡️ 🎰
            
            @%s lost %s Ehre
            TEXT;
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf($text, $user->getName(), NumberFormat::money($amount)),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
            $this->manager->flush();
            $this->telegramService->answerCallbackQuery($callbackQuery);
            $this->updateJackpot($update, $previousJackpot, $jackpot->getAmount());
            return;
        }
        // if all 3 are the same
        if ($result[0] === $result[1] && $result[1] === $result[2]) {
            $amount = Honor::currency(1);
            $text = <<<TEXT
            🎰 %s 🎰
            
            @%s wins %s Ehre
            TEXT;
            $this->honorService->addHonor($chat, $user, $amount);
            $this->telegramService->sendText($chat->getChatId(), sprintf(
                $text,
                implode(' ', $result),
                $user->getName(),
                NumberFormat::money($amount),
            ), threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId());
            $this->manager->flush();
            $this->telegramService->answerCallbackQuery($callbackQuery);
            $this->updateJackpot($update, $previousJackpot, $jackpot->getAmount());
            return;
        }
        $this->manager->flush();
        $this->updateJackpot($update, $previousJackpot, $jackpot->getAmount());
        $this->telegramService->answerCallbackQuery(
            $callbackQuery,
            implode(' ', $result),
            true,
        );
    }

    public function updateJackpot(Update $update, Money $oldJackpot, Money $newJackpot): void
    {
        // do not update if the jackpot did not change
        if (NumberFormat::money($oldJackpot) === NumberFormat::money($newJackpot)) {
            return;
        }
        $this->telegramService->editMessage(
            $update->getCallbackQuery()->getMessage()->getChat()->getId(),
            $update->getCallbackQuery()->getMessage()->getMessageId(),
            $this->getStartText($newJackpot),
            replyMarkup: $this->getKeyboard(),
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
        return '!slot';
    }

}
