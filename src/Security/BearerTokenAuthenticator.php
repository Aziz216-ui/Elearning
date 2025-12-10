<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class BearerTokenAuthenticator extends AbstractAuthenticator
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function supports(Request $request): ?bool
    {
        // Support l'authentification Bearer token pour les appels API
        return $request->headers->has('Authorization') && 
               str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        // Vérifier le token stocké dans .webhook_token
        $projectDir = $this->params->get('kernel.project_dir');
        $tokenFile = $projectDir . '/.webhook_token';
        
        if (file_exists($tokenFile)) {
            $storedToken = trim(file_get_contents($tokenFile));
            if ($token !== $storedToken) {
                throw new CustomUserMessageAuthenticationException('Invalid API token');
            }

            // Charger l'utilisateur admin
            return new SelfValidatingPassport(
                new UserBadge('fedi@test.com')
            );
        }

        throw new CustomUserMessageAuthenticationException('No valid API token found');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Allow the request to proceed
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessageKey(),
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}

