<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TelegramAuthenticator extends JWTAuthenticator
{

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $eventDispatcher,
        TokenExtractorInterface $tokenExtractor,
        UserProviderInterface $userProvider,
        private UserRepository $userRepository,
    )
    {
        parent::__construct($jwtManager, $eventDispatcher, $tokenExtractor, $userProvider);
    }

    protected function loadUser(array $payload, $identity): UserInterface
    {
        if (!$identity) {
            throw new AuthenticationException('Invalid JWT Token');
        }
        return $this->userRepository->findOneBy(['telegramUserId' => $identity]);
    }

}