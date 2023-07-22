<?php

namespace App\Controller;

use App\Service\Telegram\TelegramService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    #[Route('/rickrolled/{name}', methods: ['POST'])]
    public function rickrolled(string $name, TelegramService $telegramService): Response
    {
        $telegramService->sendText(1098121923, sprintf('%s heds hops gno', $name));
        return $this->json([
            'success' => true,
        ]);
    }

}