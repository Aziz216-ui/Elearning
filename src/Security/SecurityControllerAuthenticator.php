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
        // et les requêtes JSON (getPayload) utilisées par certaines API/frontends.
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        // Si on ne trouve pas les données dans le POST, tenter le payload JSON si disponible
        if ((null === $email || null === $password) && method_exists($request, 'getPayload')) {
            $payload = $request->getPayload();
            if ($payload) {
                // getString() peut lancer si la clé n'existe pas; on utilise un fallback
                try {
                    $email = $email ?? $payload->getString('email');
                } catch (\Throwable) {
                }
                try {
                    $password = $password ?? $payload->getString('password');
                } catch (\Throwable) {
                }
                try {
                    $csrfToken = $csrfToken ?? $payload->getString('_csrf_token');
                } catch (\Throwable) {
                }
            }
        }

        // Si encore null, remplacer par chaîne vide pour éviter des valeurs nulles dans la session
        $email = $email ?? '';

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password ?? ''),
            [
                new CsrfTokenBadge('authenticate', $csrfToken ?? ''),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
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