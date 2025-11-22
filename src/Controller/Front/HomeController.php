<?php

namespace App\Controller\front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        // Ici tu peux afficher l'utilisateur connecté
        $user = $this->getUser();

        return $this->render('front/home/index.html.twig', [
            'user' => $user,
        ]);
    }
}
