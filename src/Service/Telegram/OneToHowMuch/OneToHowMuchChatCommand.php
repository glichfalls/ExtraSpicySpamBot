<?php

namespace App\Service\Telegram\OneToHowMuch;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\OneToHowMuch\OneToHowMuchRound;
use App\Entity\OneToHowMuch\OneToHowMuchRoundFactory;
use App\Entity\User\User;
use App\Repository\OneToHowMuchRoundRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class OneToHowMuchChatCommand extends AbstractTelegramChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = '1zwv';

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private OneToHowMuchRoundRepository $repository,
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
        list($_, $roundId, $amount) = explode(':', $update->getCallbackQuery()->getData());
        $round = $this->repository->find($roundId);
        if ($round === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'round not found', false);
            return;
        }
        if (!$round->isAccepted()) {
            if ($round->getOpponent()->getId() !== $user->getId()) {
                $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'you are not the opponent', false);
                return;
            }
            $round->setAccepted(true);
            $round->setRange((int) $amount);
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'accepted', false);
            $this->telegramService->sendText(
                $chat->getChatId(),
                'Accepted! Chose your number',
                threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
                replyMarkup: $this->getKeyboard($round),
            );
            $this->manager->flush();
            return;
        }
        if (
            $round->getChallenger()->getId() !== $user->getId() ||
            $round->getOpponent()->getId() !== $user->getId()
        ) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'you are not a participant', false);
            return;
        }
        if ($round->getChallenger()->getId() === $user->getId()) {
            if ($round->getChallengerNumber() !== null) {
                $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'you already chose a number', false);
                return;
            }
            $round->setChallengerNumber((int) $amount);
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'number chosen', false);
        }
        if ($round->getOpponent()->getId() === $user->getId()) {
            if ($round->getOpponentNumber() !== null) {
                $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'you already chose a number', false);
                return;
            }
            $round->setOpponentNumber((int) $amount);
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'number chosen', false);
        }
        if ($round->getOpponentNumber() !== null && $round->getChallengerNumber() !== null) {
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf(
                    '@%s: %d, @%s: %d',
                    $round->getChallenger()->getName(),
                    $round->getChallengerNumber(),
                    $round->getOpponent()->getName(),
                    $round->getOpponentNumber(),
                ),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
            );
            if ($round->getOpponentNumber() === $round->getChallengerNumber()) {
                $round->setWinner($round->getChallenger());
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf('@%s won!', $round->getChallenger()->getName()),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
                );
            } else {
                $round->setWinner($round->getOpponent());
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf('@%s won!', $round->getOpponent()->getName()),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageId(),
                );
            }
        }
        $this->manager->flush();
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^1zwv .+@(?<user>.+)/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $targets = $this->telegramService->getUsersFromMentions($update);
        if (count($targets) !== 1) {
            $this->telegramService->replyTo($message, 'please mention exactly one user');
            return;
        }
        $opponent = $targets[0];
        $round = OneToHowMuchRoundFactory::create($message->getUser(), $opponent);
        $this->manager->persist($round);
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            sprintf('@%s challenged @%s', $message->getUser()->getName(), $opponent->getName()),
            threadId: $message->getTelegramMessageId(),
            replyMarkup: $this->getKeyboard(),
        );
        $this->manager->flush();
    }

    private function getKeyboard(OneToHowMuchRound $round): InlineKeyboardMarkup
    {
        $data = [];
        if (!$round->isAccepted()) {
            for ($i = 2; $i <= $round->getRange(); $i+=5) {
                $row = [];
                for ($j = 1; $j <= 5; $j++) {
                    $row[] = [
                        'text' => $i,
                        'callback_data' => sprintf('%s:%s:%d', self::CALLBACK_KEYWORD, $round->getId(), $i),
                    ];
                }
                $data[] = $row;
            }
        } else {
            $range = $round->getRange();
            $row = [];
            for ($i = 1; $i <= $range; $i++) {
                $row[] = [
                    'text' => $i,
                    'callback_data' => sprintf('%s:%s:%d', self::CALLBACK_KEYWORD, $round->getId(), $i),
                ];
                if (count($row) == 5 || $i == $range) {
                    $data[] = $row;
                    $row = [];
                }
            }
        }
        return new InlineKeyboardMarkup($data);
    }

}