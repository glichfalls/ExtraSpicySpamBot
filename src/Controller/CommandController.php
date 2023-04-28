<?php

namespace App\Controller;

use App\Service\TelegramMessageSender;
use BoShurik\TelegramBotBundle\Telegram\Telegram;
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

    #[Route('/check')]
    public function checkId(string $chatId): Response
    {
        $message = $this->messageSender->send($chatId, $chatId);
        return new JsonResponse($message?->toJson(true));
    }

    #[Route('/spam')]
    public function spam(Request $request): Response
    {
        $message = $this->messageSender->spam($request->query->get('message'));
        return new JsonResponse($message?->toJson(true));
    }

    #[Route('/friday')]
    public function friday(): Response
    {
        $messages = $this->messageSender->video('https://extra-spicy-spam.portner.dev/assets/video/friday-sailor.mp4');
        return new JsonResponse($messages);
    }

    #[Route('/send/{chatId}')]
    public function send(string $chatId, Request $request): Response
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