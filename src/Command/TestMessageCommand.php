<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TelegramBot\Api\BotApi;

#[AsCommand('telegram:test')]
class TestMessageCommand extends Command
{

    public function __construct(private readonly BotApi $bot)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('chatId', InputArgument::REQUIRED)
            ->addArgument('message', InputArgument::REQUIRED);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            $chatId = $input->getArgument('chatId');
            $message = $input->getArgument('message');
            $output->writeln(sprintf('Sending message %s to chat %s', $message, $chatId));
            $sentMessage = $this->bot->sendMessage($chatId, $message);
            $output->writeln(sprintf('Message sent with id "%s"', $sentMessage->getMessageId()));
            return self::SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln(sprintf('Error: %s', $exception->getMessage()));
            return self::FAILURE;
        }
    }

}