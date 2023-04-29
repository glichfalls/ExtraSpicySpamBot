<?php

namespace App\Controller;

use App\Service\TelegramBaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommandController extends AbstractController
{

    public function __construct(
        private TelegramBaseService $telegramService,
        private string $extraSpicySpamChatId,
    )
    {
    }

    #[Route('/spam')]
    public function spam(Request $request): Response
    {
        $this->telegramService->sendText(
            $this->extraSpicySpamChatId,
            $request->query->get('message')
        );
        return new RedirectResponse('/');
    }

    #[Route('/friday')]
    public function friday(): Response
    {
        $this->telegramService->sendVideo(
            $this->extraSpicySpamChatId,
            'https://extra-spicy-spam.portner.dev/assets/video/friday-sailor.mp4'
        );
        return new RedirectResponse('/');
    }

}