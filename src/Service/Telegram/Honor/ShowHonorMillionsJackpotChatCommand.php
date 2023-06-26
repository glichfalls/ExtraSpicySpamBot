<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Message\Message;
use App\Repository\DrawRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class ShowHonorMillionsJackpotChatCommand extends AbstractTelegramChatCommand
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
        return preg_match('/^!jackpot/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $draw = $this->drawRepository->getByChatAndDate($message->getChat(), new \DateTime());
        if ($draw === null) {
            $this->telegramService->replyTo($message, 'there is no draw for this chat');
            return;
        }
        $jackpot = $draw->getJackpot();
        $this->telegramService->replyTo($message, sprintf('the jackpot is %d ehre', $jackpot));
    }

}