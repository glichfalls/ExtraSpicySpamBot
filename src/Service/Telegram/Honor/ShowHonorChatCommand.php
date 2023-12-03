<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Message\Message;
use App\Service\Honor\HonorService;
use App\Service\Telegram\TelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

final readonly class ShowHonorChatCommand implements TelegramChatCommand
{

    public function __construct(
        private TranslatorInterface $translator,
        private TelegramService $telegram,
        private HonorService $honorService,
    ) {
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(honor|ehre)/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $total = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        $this->telegram->replyTo($message, $this->translator->trans('telegram.honor.show', [
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
