<?php

namespace App\Controller\front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeStatController extends AbstractController
{
    #[Route('/home/stat', name: 'app_home_stat')]
    public function index(): Response
    {
        return $this->render('front/home_stat/index.html.twig', [
            'controller_name' => 'HomeStatController',
        ]);
    }
}
