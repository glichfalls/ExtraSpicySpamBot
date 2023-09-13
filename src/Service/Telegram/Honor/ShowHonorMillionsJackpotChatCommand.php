<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Message\Message;
use App\Repository\DrawRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
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
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!jackpot/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $date = new \DateTime();
        if ($date->format('H') >= 22) {
            // after 22:00 the jackpot is for the next day
            $date->modify('+2 hours');
        }
        $draw = $this->drawRepository->getByChatAndDate($message->getChat(), $date);
        if ($draw === null) {
            $this->telegramService->replyTo($message, 'there is no draw for this chat');
            return;
        }
        $jackpot = $draw->getJackpot();
        $this->telegramService->replyTo($message, sprintf(
            'the jackpot is %s Ehre (+%s today)',
            NumberFormat::format($jackpot),
            NumberFormat::format($draw->getGamblingLosses()),
        ));
    }

    public function getSyntax(): string
    {
        return '!jackpot';
    }

    public function getDescription(): string
    {
        return 'show the jackpot';
    }

}
