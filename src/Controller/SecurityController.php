<?php

namespace App\Controller;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class SecurityController extends AbstractController implements TelegramCallbackQueryListener
{

    public function __construct(
        private UserRepository $userRepository,
        private TelegramService $telegramService,
        private ChatRepository $chatRepository,
    )
    {
    }

    #[Route('/api/me')]
    public function getMe(): Response
    {
        return $this->json($this->getUser());
    }

    #[Route('/auth/check', name: 'login_check')]
    public function loginCheck(): void
    {
        // The security layer will intercept this request and proceed to the login.
        // If it is successful, the user is sent to the default success route.
        // If it fails, the user is sent to the default failure route.
        throw new \LogicException('This code should never be reached');
    }

    #[Route('/auth/telegram', name: 'telegram_login', methods: ['POST'])]
    public function requestLoginLink(LoginLinkHandlerInterface $loginLinkHandler, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        if ($name === null) {
            return $this->json([
                'message' => 'name not provided',
            ], status: Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->getByName($name);
        if ($user === null) {
            return $this->json([
                'message' => 'user not found',
            ], status: Response::HTTP_NOT_FOUND);
        }

        $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
        $loginLink = $loginLinkDetails->getUrl();

        $userChat = $this->chatRepository->getChatByUser($user);

        if ($userChat === null) {
            return $this->json([
                'message' => 'no chat found for user',
            ], status: Response::HTTP_PRECONDITION_FAILED);
        }

        if ($request->getHost() !== 'localhost') {
            $this->telegramService->sendText(
                $userChat->getChatId(),
                'click the button below to login',
                replyMarkup: $this->getLoginKeyboard($loginLink, $request),
            );
        }

        if ($this->getParameter('kernel.environment') === 'dev') {
            return $this->json([
                'success' => true,
                'link' => $loginLink,
            ]);
        }
        return $this->json([
            'success' => true,
        ]);
    }

    private function getLoginKeyboard(string $link, Request $request): InlineKeyboardMarkup
    {
        // telegram doesn't allow localhost links
        if ($request->getHost() === 'localhost') {
            $link = preg_replace('/localhost:\d+/', 'telegram.org', $link);
        }
        return new InlineKeyboardMarkup([
            [
                ['text' => 'login', 'url' => $link],
            ],
        ]);
    }

    public function getCallbackKeyword(): string
    {
        return 'login';
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {

    }

}