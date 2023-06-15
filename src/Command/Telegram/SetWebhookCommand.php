<?php

namespace App\Command\Telegram;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('telegram:set-webhook')]
class SetWebhookCommand extends Command
{

    public function __construct(private HttpClientInterface $httpClient, private string $telegramToken)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('host', mode: InputArgument::REQUIRED, description: 'Host to set webhook to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getArgument('host');
        $url = sprintf(
            'https://api.telegram.org/bot%s/setWebhook?url=https://%s/_telegram/%s/',
            $this->telegramToken,
            $host,
            $this->telegramToken,
        );
        $response = $this->httpClient->request('POST', $url);
        if ($response->getStatusCode() === 200) {
            $output->writeln('Webhook set');
            return Command::SUCCESS;
        }
        $output->writeln('Webhook not set');
        return Command::FAILURE;
    }

}