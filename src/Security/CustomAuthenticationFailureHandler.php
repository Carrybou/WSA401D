<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // Ajout de la classe Response
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Security;

class CustomAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response // Ajout du type de retour Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, new AuthenticationException('Identifiants invalides'));

        return new RedirectResponse($this->router->generate('app_login'));
    }
}