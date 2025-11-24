<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeStatController extends AbstractController
{
    #[Route('/home/stat', name: 'app_home_stat')]
    public function index(): Response
    {
        // Données factices pour le démonstration
        $stats = [
            'users_count' => 1250,
            'courses_count' => 42,
            'completed_quizzes' => 876,
            'popular_courses' => range(1, 3),
            'instructors' => range(1, 4),
            'testimonials' => range(1, 4)
        ];

        return $this->render('front/home_stat/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}
