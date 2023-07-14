<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\DrawRepository;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class HonorRouletteChatCommand extends AbstractTelegramChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = 'roulette';

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private HonorRepository $honorRepository,
        private DrawRepository $drawRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $data = explode(';', $callbackQuery->getData());
        if (count($data) === 3) {
            $amount = (int) $data[1];
            $currentHonor = $this->honorRepository->getHonorCount($user, $chat);
            if ($currentHonor < $amount) {
                $this->telegramService->answerCallbackQuery(
                    $callbackQuery,
                    sprintf('you dont have enough Ehre to bet %d Ehre', $amount),
                    true,
                );
            } else {
                $bet = $data[2];
                $result = $this->roll($chat, $user, $bet, $amount);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf(
                        '%s %s %d Ehre (%s -> %d %s)',
                        $user->getName(),
                        $result['amount'] > 0 ? 'won' : 'lost',
                        abs($result['amount']),
                        $bet,
                        $result['number'],
                        $this->getColorEmojiByNumber($result['number']),
                    ),
                    threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                );
                $this->telegramService->answerCallbackQuery(
                    $callbackQuery,
                    sprintf(
                        'You %s %d Ehre (%d %s)',
                        $result['amount'] > 0 ? 'won' : 'lost',
                        abs($result['amount']),
                        $result['number'],
                        $this->getColorEmojiByNumber($result['number']),
                    ),
                    true,
                );
            }
        }
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!roulette (?<amount>\d+)\s?(?<bet>(red|black|1-18|19-36|1-12|13-24|25-36|[0-9]|[1-2][0-9]|3[0-6]))?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $initialAmount = (int) $matches['amount'];
        $bet = $matches['bet'] ?? null;
        if ($bet === null) {
            $this->telegramService->sendText(
                $message->getChat()->getChatId(),
                sprintf('chose a bet for %d Ehre', $initialAmount),
                threadId: $message->getTelegramThreadId(),
                replyMarkup: $this->getBoardKeyboard($initialAmount),
            );
            return;
        }
        $currentHonor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($currentHonor < $initialAmount) {
            $this->telegramService->replyTo($message, 'not enough ehre');
        } else {
            $result = $this->roll($message->getChat(), $message->getUser(), $bet, $initialAmount);
            $this->telegramService->replyTo(
                $message,
                sprintf(
                    'the number is %d %s. %s %s %d ehre.',
                    $result['number'],
                    $this->getColorEmojiByNumber($result['number']),
                    $message->getUser()->getName(),
                    $result['amount'] > 0 ? 'won' : 'lost',
                    abs($result['amount']),
                ),
            );
        }
    }

    #[ArrayShape(['number' => "int", 'color' => "string", 'amount' => "int"])]
    private function roll(Chat $chat, User $user, string $bet, int $initialAmount): array
    {
        $number = random_int(0, 36);
        $colorValue = $this->getColorByNumber($number);
        $amount = match ($bet) {
            'red' => $colorValue === 'red' && $number !== 0 ? $initialAmount : -$initialAmount,
            'black' =>$colorValue === 'black' ? $initialAmount : -$initialAmount,
            '1-12' => $number >= 1 && $number <= 12 ? ($initialAmount * 3) - $initialAmount : -$initialAmount,
            '13-24' => $number >= 13 && $number <= 24 ?  ($initialAmount * 3) - $initialAmount : -$initialAmount,
            '25-36' => $number >= 25 && $number <= 36 ?  ($initialAmount * 3) - $initialAmount : -$initialAmount,
            '1-18' => $number >= 1 && $number <= 18 ? $initialAmount : -$initialAmount,
            '19-36' => $number >= 19 && $number <= 36 ? $initialAmount : -$initialAmount,
            default => $number === (int)$bet ? ($initialAmount * 36) - $initialAmount : -$initialAmount,
        };
        if ($number < 0) {
            // add loss to the jackpot of the next honor millions draw
            $draw = $this->drawRepository->getActiveDrawByChat($chat);
            $draw?->setGamblingLosses($draw->getGamblingLosses() + abs($amount));
        }
        $this->manager->persist(HonorFactory::create($chat, null, $user, $amount));
        $this->manager->flush();
        return [
            'number' => $number,
            'color' => $colorValue,
            'amount' => $amount,
        ];
    }

    public function getHelp(): string
    {
        return '!roulette <amount> <bet>    Bets: red, black, 1-18, 19-36, 1-12, 13-24, 25-36, single number from 0-36';
    }

    private function getBoard(): array
    {
        return [
            [ 0 => 'green' ],
            [ 1 => 'red', 2 => 'black', 3 => 'red'],
            [ 4 => 'black', 5 => 'red', 6 => 'black'],
            [ 7 => 'red', 8 => 'black', 9 => 'red'],
            [ 10 => 'black', 11 => 'black', 12 => 'red'],
            [ 13 => 'black', 14 => 'red', 15 => 'black'],
            [ 16 => 'red', 17 => 'black', 18 => 'red'],
            [ 19 => 'red', 20 => 'black', 21 => 'red'],
            [ 22 => 'black', 23 => 'red', 24 => 'black'],
            [ 25 => 'red', 26 => 'black', 27 => 'red'],
            [ 28 => 'black', 29 => 'black', 30 => 'red'],
            [ 31 => 'black', 32 => 'red', 33 => 'black'],
            [ 34 => 'red', 35 => 'black', 36 => 'red'],
        ];
    }

    private function getColorEmojiByNumber(int $number): ?string
    {
        $board = $this->getBoard();
        foreach ($board as $row) {
            foreach ($row as $numberInRow => $color) {
                if ($numberInRow === $number) {
                    if ($color === 'green') {
                        return '🟢';
                    }
                    return $color === 'red' ? '🔴' : '⚫️';
                }
            }
        }
        return null;
    }

    private function getColorByNumber(int $number): ?string
    {
        $board = $this->getBoard();
        foreach ($board as $row) {
            foreach ($row as $numberInRow => $color) {
                if ($numberInRow === $number) {
                    return $color;
                }
            }
        }
        return null;
    }

    private function getBoardKeyboard(int $amount): InlineKeyboardMarkup
    {
        $board = $this->getBoard();
        $keyboard = [];
        foreach ($board as $data) {
            $row = [];
            foreach ($data as $number => $color) {
                  $row[] = [
                      'text' => sprintf(
                          '%s %d',
                          $color === 'green' ? '🟢' : ($color === 'red' ? '🔴' : '⚫️'),
                          $number
                      ),
                      'callback_data' => sprintf('%s;%d;%d', self::CALLBACK_KEYWORD, $amount, $number)
                  ];
            }
            $keyboard[] = $row;
        }
        $keyboard[] = [
            ['text' => '1-12', 'callback_data' => sprintf('%s;%d;%s', self::CALLBACK_KEYWORD, $amount, '1-12')],
            ['text' => '13-24', 'callback_data' => sprintf('%s;%d;%s', self::CALLBACK_KEYWORD, $amount, '13-24')],
            ['text' => '25-36', 'callback_data' => sprintf('%s;%d;%s', self::CALLBACK_KEYWORD, $amount, '25-36')],
        ];
        $keyboard[] = [
            ['text' => '1-18', 'callback_data' => sprintf('%s;%d;%s', self::CALLBACK_KEYWORD, $amount, '1-18')],
            ['text' => '19-36', 'callback_data' => sprintf('%s;%d;%s', self::CALLBACK_KEYWORD, $amount, '19-36')],
        ];
        $keyboard[] = [
            ['text' => 'red', 'callback_data' => sprintf('%s;%d;%s', self::CALLBACK_KEYWORD, $amount, 'red')],
            ['text' => 'black', 'callback_data' => sprintf('%s;%d;%s', self::CALLBACK_KEYWORD, $amount, 'black')],
        ];
        return new InlineKeyboardMarkup($keyboard);
    }

}