<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Message\Message;
use App\Service\Honor\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class ShowHonorChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(honor|ehre)/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $total = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.show', [
            'amount' => NumberFormat::money($total),
        ]));
    }

    public function getSyntax(): string
    {
        return '!ehre';
    }

    public function getDescription(): string
    {
        return 'show your current ehre';
    }

}
