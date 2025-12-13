<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityControllerAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        // Supporter à la fois les formulaires classiques (application/x-www-form-urlencoded)
        // et les requêtes JSON (application/json) utilisées par certains frontends.
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        // Si on ne trouve pas les données dans le POST, tenter d'extraire depuis le JSON brut
        if ((null === $email || null === $password) && $request->getContent()) {
            $contentType = $request->headers->get('content-type', '');
            if (strpos($contentType, 'application/json') !== false) {
                try {
                    $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $data = null;
                }

                if (is_array($data)) {
                    $email = $email ?? ($data['email'] ?? null);
                    $password = $password ?? ($data['password'] ?? null);
                    $csrfToken = $csrfToken ?? ($data['_csrf_token'] ?? null);
                }
            }
        }

        // Assurer des valeurs non nulles
        $email = $email ?? '';
        $password = $password ?? '';

        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                // s'assurer que l'id 'authenticate' correspond à votre formulaire
                new CsrfTokenBadge('authenticate', $csrfToken ?? ''),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Ignorer le targetPath sauvegardé et forcer la redirection par rôle

        $roles = $token->getRoleNames();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse('/admin');
        }

        if (in_array('ROLE_USER', $roles, true)) {
            return new RedirectResponse('/plan');
        }

        // Fallback redirection
        return new RedirectResponse('/plan');
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}