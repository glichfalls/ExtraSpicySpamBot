<?php

namespace App\Command\Telegram;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('telegram:webhook:dev')]
class EnableDevWebhookCommand extends Command
{

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $telegramToken
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('host', mode: InputArgument::REQUIRED, description: 'host (ngrok, etc.)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $apiUrl = sprintf('%s/_telegram/%s/', $input->getArgument('host'), $this->telegramToken);
            $response = $this->httpClient->request('POST', sprintf(
                'https://api.telegram.org/bot%s/setWebhook?url=%s',
                $this->telegramToken,
                $apiUrl,
            ));
            if ($response->getStatusCode() === 200) {
                $output->writeln(sprintf('Webhook set to %s', $this->telegramToken, $apiUrl));
                return Command::SUCCESS;
            }
            $output->writeln(sprintf('Error: %s', $response->getContent(false)));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $output->writeln(sprintf('Error: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

}
