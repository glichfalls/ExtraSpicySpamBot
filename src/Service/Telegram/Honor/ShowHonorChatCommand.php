<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Message\Message;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class ShowHonorChatCommand extends AbstractTelegramChatCommand
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
        return preg_match('/^!(honor|ehre)/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $total = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.show', ['amount' => $total]));
    }

    public function getHelp(): string
    {
        return '!ehre   shows your current ehre';
    }

}