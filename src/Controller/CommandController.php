<?php

namespace App\Controller;

use App\Service\TelegramMessageSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommandController extends AbstractController
{

    public function __construct(private TelegramMessageSender $messageSender)
    {
    }

    #[Route('/send/{chatId}')]
    public function test(string $chatId, Request $request): Response
    {
        $message = $this->messageSender->send($chatId, $request->query->get('message'));
        return new JsonResponse($message?->toJson(true));
    }

    #[Route('/send/{chatId}', methods: ['POST'])]
    public function sendMessage(string $chatId, Request $request): Response
    {
        $message = $this->messageSender->send($chatId, $request->request->get('message'));
        return new JsonResponse($message?->toJson(true));
    }

}