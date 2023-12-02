<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Service\Browser\ChartRenderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsImageController extends AbstractController
{

    public function __construct(
        private readonly ChartRenderService $chartRenderService,
    ) {
    }

    #[Route('/telegram/{chatId}/stats', methods: ['GET'])]
    public function chatStats(
        Request $request,
        string $chatId,
        ChatRepository $chatRepository,
        MessageRepository $messageRepository,
    ): Response {
        $chat = $chatRepository->find($chatId);
        $messages = $messageRepository->getTextOccurrencesByDate($chat, $request->query->get('query', ''));
        $data = [
            'data' => array_column($messages, 'count'),
            'labels' => array_column($messages, 'date'),
        ];
        $base64Image = $this->chartRenderService->render($data);
        $response = new Response();
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->setContent(base64_decode($base64Image));
        return $response;
    }

}
