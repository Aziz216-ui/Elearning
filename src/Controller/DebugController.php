<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/debug')]
class DebugController extends AbstractController
{
    #[Route('/auth', name: 'debug_auth')]
    public function authStatus(): Response
    {
        $user = $this->getUser();
        
        $data = [
            'is_authenticated' => $this->isGranted('IS_AUTHENTICATED_REMEMBERED'),
            'is_fully_authenticated' => $this->isGranted('IS_AUTHENTICATED_FULLY'),
            'user_object' => $user ? [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'class' => get_class($user),
            ] : null,
            'session_id' => session_id(),
            'firewall' => 'main',
        ];

        return new Response('<pre>' . print_r($data, true) . '</pre>');
    }
}
