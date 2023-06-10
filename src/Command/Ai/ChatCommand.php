<?php

namespace App\Command\Ai;

use App\Service\OpenApi\OpenAiCompletionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('ai:chat')]
class ChatCommand extends Command
{

    public function __construct(private OpenAiCompletionService $chatService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('message', null, 'Message to chat');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($input->getArgument('message'));
        $response = $this->chatService->generateImage($input->getArgument('message'));
        if ($response === null) {
            $output->writeln('Error');
            return Command::FAILURE;
        }
        $output->write(json_encode($response, JSON_PRETTY_PRINT));
        return Command::SUCCESS;
    }

}