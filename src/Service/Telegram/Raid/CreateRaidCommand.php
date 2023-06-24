<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Honor\Raid\RaidFactory;
use App\Entity\Message\Message;
use App\Repository\HonorRepository;
use App\Repository\RaidRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class CreateRaidCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface  $manager,
        TranslatorInterface     $translator,
        LoggerInterface         $logger,
        TelegramService         $telegramService,
        private HonorRepository $honorRepository,
        private RaidRepository  $raidRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!raid\s*@(?<name>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $name = $matches['name'];
        $targets = $this->telegramService->getUsersFromMentions($update);
        if (count($targets) !== 1) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.onlyOneUserCanBeRaided'));
            return;
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
        $latestRaid = $this->raidRepository->getLatestRaid($chat);
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
        $raid = RaidFactory::create($chat, $message->getUser(), $target);
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->videoReplyTo($message, 'https://extra-spicy-spam.portner.dev/assets/video/raid.mp4');
        $this->telegramService->sendText($chat->getChatId(), sprintf(
            '%s started a raid against %s! to join write !support and to defend write !defend. To start the raid write !start raid',
            $message->getUser()->getName(),
            $target->getName()
        ));
    }

}