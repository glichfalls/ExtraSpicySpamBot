<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Raid;

use App\Entity\Message\Message;
use App\Exception\RaidGuardException;
use App\Utils\NumberFormat;
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
        try {
            $raid = $this->raidService->createRaid($chat, $message->getUser(), $target);
            $this->manager->persist($raid);
            $this->manager->flush();
            $this->telegramService->videoReplyTo($message, 'https://extra-spicy-spam.portner.dev/assets/video/raid.mp4');
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf(
                    '%s started a raid against %s! %s Ehre will be raided.',
                    $message->getUser()->getName(),
                    $target->getName(),
                    NumberFormat::money($raid->getAmount()),
                ),
                threadId: $message->getTelegramThreadId(),
                replyMarkup: $this->getRaidKeyboard($raid),
            );
        } catch (RaidGuardException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'Raid Guard protected %s from %s!',
                $exception->target->getName(),
                $exception->leader->getName(),
            ));
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
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
