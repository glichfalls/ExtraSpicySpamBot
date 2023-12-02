<?php

namespace App\Controller;

use App\Service\Browser\ChartRenderService;
use App\Service\Telegram\TelegramService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    #[Route('/', methods: ['GET'])]
    public function index(string $frontendUrl): Response
    {
        return $this->redirect($frontendUrl);
    }

    #[Route('/rickrolled/{name}', methods: ['POST'])]
    public function rickrolled(string $name, TelegramService $telegramService): Response
    {
        $telegramService->sendText(1098121923, sprintf('%s heds hops gno', $name));
        return $this->json([
            'success' => true,
        ]);
    }

    #[Route('/chart', methods: ['GET'])]
    public function chart(ChartRenderService $service): Response
    {
        $base64Image = $service->render([
            'data' => [1,2,3],
            'labels' => ['a', 'b', 'c'],
        ]);
        $response = new Response();
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->setContent(base64_decode($base64Image));
        return $response;
    }

    #[Route('/test', methods: ['GET'])]
    public function test(): Response
    {
        return $this->render('chart/chart.html.twig', [
            'data' => [1,2,3],
            'labels' => ['a', 'b', 'c'],
        ]);
    }

}