<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\HonorMillions\Draw\DrawFactory;
use App\Entity\Message\Message;
use App\Repository\DrawRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class CreateHonorMillionsDrawChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private DrawRepository $drawRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!create draw/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $draw = $this->drawRepository->getByChatAndDate($message->getChat(), new \DateTime());
        if ($draw !== null) {
            $this->telegramService->replyTo($message, 'there is already a draw for this chat');
            return;
        }
        $draw = DrawFactory::create($message->getChat(), new \DateTime());
        $draw->setPreviousJackpot(0);
        $this->manager->persist($draw);
        $this->manager->flush();
        $this->telegramService->replyTo($message, 'created draw for this chat');
    }

}