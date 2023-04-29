<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GithubController extends AbstractController
{

    public function __construct(
        private LoggerInterface $logger,
        private string $githubWebhookSecret
    )
    {

    }

    #[Route('/github/webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $this->validate($request);

        $data = json_decode($request->getContent(), true);

        if ($data['action'] === 'completed') {
            $application = new Application($this->container->get('kernel'));
            $application->setAutoExit(false);
            $output = new BufferedOutput();
            $application->run(new ArrayInput(['command' => 'cache:clear']), $output);
            $this->logger->info($output->fetch());
        }

        return new Response('ok', 200);
    }

    private function validate(Request $request)
    {
        $signatureHeader = $request->headers->get('x-hub-signature');

        if ($signatureHeader === null) {
            throw new \RuntimeException('Missing signature header');
        }

        [$algo, $signature] = explode('=', $signatureHeader, 2) + ['', ''];

        if (!in_array($algo, hash_algos(), true)) {
            throw new \RuntimeException('Invalid signature algorithm');
        }

        if (!hash_equals($signature, hash_hmac($algo, $request->getContent(), $this->githubWebhookSecret))) {
            throw new \RuntimeException('Invalid signature');
        }
    }

}