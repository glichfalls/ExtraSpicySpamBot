<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler as LexikAuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationSuccessHandler extends LexikAuthenticationSuccessHandler
{
    public function __construct(
        private string $frontendUrl,
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $dispatcher,
        $cookieProviders = [],
        bool $removeTokenFromBodyWhenCookiesUsed = true,
    )
    {
        parent::__construct($jwtManager, $dispatcher, $cookieProviders, $removeTokenFromBodyWhenCookiesUsed);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $response = parent::onAuthenticationSuccess($request, $token);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $content = json_decode($response->getContent(), true);
        $token = $content['token'] ?? null;

        // change response to 307 Temporary Redirect
        // add jwt token from body to header
        $response->setStatusCode(Response::HTTP_TEMPORARY_REDIRECT);
        $response->headers->set('Location', sprintf('%s/auth?token=%s', $this->frontendUrl, $token));
        $response->setContent(null);
        return $response;
    }
}