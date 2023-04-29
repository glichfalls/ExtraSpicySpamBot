<?php

namespace App\Controller;

use App\Service\TelegramBaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommandController extends AbstractController
{

    public function __construct(private TelegramBaseService $telegramService)
    {
    }

    #[Route('/spam')]
    public function spam(Request $request): Response
    {
        $message = $this->telegramService->spam($request->query->get('message'));
        return new JsonResponse($message?->toJson(true));
    }

}