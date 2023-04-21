<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        $this->addArgument('chatId');
        $this->addArgument('message');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            $chatId = $input->getArgument('chatId');
            $message = $input->getArgument('message');
            $output->writeln(sprintf('Sending message "%s" to chat "%s"', $message, $chatId));
            $this->bot->sendMessage($chatId, $message);
            return self::SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln($exception->getMessage());
            return self::FAILURE;
        }
    }

}