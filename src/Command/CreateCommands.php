<?php

namespace App\Command;

use BoShurik\TelegramBotBundle\Telegram\Command\CommandRegistry;
use BoShurik\TelegramBotBundle\Telegram\Command\PublicCommandInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('telegram:commands:create')]
class CreateCommands extends Command
{

    public function __construct(
        private string $botToken,
        private HttpClientInterface $client,
        private CommandRegistry $registry,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commands = $this->registry->getCommands();
        $response = $this->client->request('POST', $this->getUrl(), [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'commands' => array_map(fn(PublicCommandInterface $command) => [
                    'command' => $command->getName(),
                    'description' => $command->getDescription(),
                ], $commands),
            ]),
        ]);
        $output->writeln($response->getContent());
        return $response->getStatusCode() === 200 ? self::SUCCESS : self::FAILURE;
    }

    private function getUrl(): string
    {
        return sprintf('https://api.telegram.org/bot%s/setMyCommands', $this->botToken);
    }

}