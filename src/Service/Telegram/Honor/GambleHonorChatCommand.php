<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GambleHonorChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface  $manager,
        TranslatorInterface     $translator,
        LoggerInterface         $logger,
        TelegramService         $telegramService,
        private HonorRepository $honorRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!gamble\s(?<count>\d+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $count = (int) $matches['count'];
        $currentHonor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($currentHonor < $count) {
            $this->telegramService->replyTo($message, 'not enough honor');
        } else {
            if (rand(0, 1) === 1) {
                $this->manager->persist(HonorFactory::create($message->getChat(), $message->getUser(), $message->getUser(), $count));
                $this->manager->flush();
                $this->telegramService->replyTo($message, sprintf('you have won %d honor', $count));
            } else {
                $this->manager->persist(HonorFactory::create($message->getChat(), $message->getUser(), $message->getUser(), -$count));
                $this->manager->flush();
                $this->telegramService->replyTo($message, sprintf('you have lost %d honor', $count));
            }
        }
    }

}