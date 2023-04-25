<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class CommandController extends AbstractController
{

    public function __construct(
        private KernelInterface $kernel,
        private LoggerInterface $logger,
    )
    {
    }

    #[Route('/test/{chatId}', name: 'test')]
    public function test(string $chatId, Request $request): Response
    {
        $output = new BufferedOutput();

        $input = new ArrayInput([
            'command' => 'telegram:test',
            'chatId' => $chatId,
            'message' => $request->query->getAlnum('message'),
        ]);

        $status = $this->run($input, $output);

        return $this->createResponse($status, $output);
    }

    private function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            $app = new Application($this->kernel);
            $app->setAutoExit(false);
            return $app->run($input, $output);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function createResponse(int $status, BufferedOutput $output): JsonResponse
    {
        return new JsonResponse([
            'status' => $status,
            'output' => array_filter(explode(PHP_EOL, $output->fetch())),
        ]);
    }

}