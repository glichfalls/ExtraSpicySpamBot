<?php

namespace App\Service\Telegram\General;

use App\Entity\Message\Message;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class HelpChatCommand extends AbstractTelegramChatCommand
{

    /**
     * @var iterable<TelegramChatCommand>
     */
    private iterable $commands;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        #[TaggedIterator('telegram.chat_command')]
        iterable $commands,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
        $this->commands = $commands;
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^[!\/]help$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $help = [];
        foreach ($this->commands as $command) {
            $help[] = sprintf(
                '<code>%s</code>%s%s%s',
                $command->getSyntax(),
                PHP_EOL,
                $command->getDescription(),
                PHP_EOL,
            );
        }
        $this->logger->error(implode(PHP_EOL, $help));
        $this->telegramService->replyTo(
            $message,
            implode(PHP_EOL, $help),
            parseMode: 'HTML',
        );
    }

    public function getHelp(): string
    {
        return 'show this help';
    }

}