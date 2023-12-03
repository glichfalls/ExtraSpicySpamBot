<?php

namespace App\Service\Telegram\General;

use App\Entity\Message\Message;
use App\Service\Telegram\TelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use TelegramBot\Api\Types\Update;

readonly class HelpChatCommand implements TelegramChatCommand
{

    /**
     * @var iterable<TelegramChatCommand>
     */
    private iterable $commands;

    public function __construct(
        #[TaggedIterator('telegram.chat_command')] iterable $commands,
        private TelegramService $telegram,
    ) {
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
                str_replace(['<', '>'], ['(', ')'], $command->getSyntax()),
                PHP_EOL,
                $command->getDescription(),
                PHP_EOL,
            );
        }
        $this->telegram->replyTo(
            $message,
            implode(PHP_EOL, $help),
            parseMode: 'HTML',
        );
    }

    public function getSyntax(): string
    {
        return '!help';
    }

    public function getDescription(): string
    {
        return 'Show this help message';
    }

}
