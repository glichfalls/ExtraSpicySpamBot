<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Honor\Raid\RaidFactory;
use App\Entity\Message\Message;
use App\Repository\HonorRepository;
use App\Repository\RaidRepository;
use App\Repository\UserRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class CreateRaidCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private HonorRepository $honorRepository,
        private RaidRepository  $raidRepository,
        private UserRepository $userRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!raid\s*@?(?<name>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $name = $matches['name'];
        $targets = $this->telegramService->getUsersFromMentions($update);
        if (count($targets) > 1) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.onlyOneUserCanBeRaided'));
            return;
        }
        if (count($targets) === 0) {
            $target = $this->userRepository->getByFirstName($message->getChat(), $name);
            if ($target === null) {
                $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.userNotFound', [
                    'user' => $name
                ]));
                return;
            }
        }
        $target = $targets[0];
        if ($target === null) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.userNotFound', [
                'user' => $name
            ]));
            return;
        }
        if ($target->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'you can\'t raid yourself');
            return;
        }
        $chat = $message->getChat();
        if ($this->raidRepository->hasActiveRaid($chat)) {
            $this->telegramService->replyTo($message, 'raid already active');
            return;
        }
        $latestRaid = $this->raidRepository->getLatestRaidByLeader($chat, $message->getUser());
        $diff = time() - $latestRaid->getCreatedAt()->getTimestamp();
        if ($diff < 3600) {
            $this->telegramService->replyTo($message, sprintf('please wait %d minutes', 60 - ($diff / 60)));
            return;
        }
        $targetHonorCount = $this->honorRepository->getHonorCount($target, $chat);
        if ($targetHonorCount <= 0) {
            $this->telegramService->replyTo($message, 'target has no honor, no raid possible :(');
            return;
        }
        $raidAmount = $this->getRaidAmount($targetHonorCount);
        $raid = RaidFactory::create($chat, $message->getUser(), $target, $raidAmount);
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->videoReplyTo($message, 'https://extra-spicy-spam.portner.dev/assets/video/raid.mp4');
        $this->telegramService->sendText(
            $chat->getChatId(),
            sprintf(
                '%s started a raid against %s! %s honor will be raided.',
                $message->getUser()->getName(),
                $target->getName(),
                $raidAmount,
            ),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getRaidKeyboard(),
        );
    }

    private function getRaidKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => '⚔️ support ',
                    'callback_data' => SupportRaidChatCommand::CALLBACK_KEYWORD,
                ],
                [
                    'text' => '🛡️ defend',
                    'callback_data' => DefendRaidChatCommand::CALLBACK_KEYWORD,
                ],
            ],
            [
                [
                    'text' => 'start',
                    'callback_data' => StartRaidChatCommand::CALLBACK_KEYWORD,
                ],
                [
                    'text' => 'cancel',
                    'callback_data' => CancelRaidChatCommand::CALLBACK_KEYWORD,
                ],
            ],
        ]);
    }

    private function getRaidAmount(int $targetHonorAmount): int
    {
        if (($targetHonorAmount / 2) > 100) {
            return $targetHonorAmount / 2;
        }
        return 100;
    }

    public function getSyntax(): string
    {
        return '!raid @<user>';
    }

    public function getDescription(): string
    {
        return 'starts a raid against the given user';
    }

}