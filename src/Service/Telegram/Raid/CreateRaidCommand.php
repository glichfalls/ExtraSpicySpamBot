<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Honor\Raid\RaidFactory;
use App\Entity\Message\Message;
use App\Utils\NumberFormat;
use App\Utils\Random;
use TelegramBot\Api\Types\Update;

class CreateRaidCommand extends AbstractRaidChatCommand
{

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
        if ($latestRaid !== null) {
            $diff = time() - $latestRaid->getCreatedAt()->getTimestamp();
            if ($diff < 3600) {
                $this->telegramService->replyTo($message, sprintf('please wait %d minutes', 60 - ($diff / 60)));
                return;
            }
        }
        $targetHonorCount = $this->honorRepository->getHonorCount($target, $chat);
        if ($targetHonorCount <= 0) {
            $this->telegramService->replyTo($message, 'target has no honor, no raid possible :(');
            return;
        }
        $leader = $message->getUser();
        if ($this->hasRaidGuard($target, $chat)) {
            $chance = $this->getRaidGuards($target, $chat)->apply(50);
            if (Random::getPercentChance($chance)) {
                $this->telegramService->replyTo(
                    $message,
                    sprintf(
                        'the raid guard protected %s. %s will be raided instead',
                        $target->getName(),
                        $leader->getName(),
                    )
                );
                $leader = $target;
                $target = $message->getUser();
                $targetHonorCount = $this->honorRepository->getHonorCount($target, $chat);
                if ($targetHonorCount < 10_000) {
                    $targetHonorCount = 10_000;
                }
            }
        }
        $raidAmount = $this->getRaidAmount($targetHonorCount);
        $raid = RaidFactory::create($chat, $leader, $target, $raidAmount);
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->videoReplyTo($message, 'https://extra-spicy-spam.portner.dev/assets/video/raid.mp4');
        $this->telegramService->sendText(
            $chat->getChatId(),
            sprintf(
                '%s started a raid against %s! %s Ehre will be raided.',
                $message->getUser()->getName(),
                $target->getName(),
                NumberFormat::format($raidAmount),
            ),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getRaidKeyboard($raid),
        );
    }

    private function getRaidAmount(int $targetHonorAmount): int
    {
        if (($targetHonorAmount / 2) > 100) {
            return (int) floor($targetHonorAmount / 2);
        }
        return 100;
    }

    public function getSyntax(): string
    {
        return '!raid @user';
    }

    public function getDescription(): string
    {
        return 'starts a raid against the given user';
    }

}
