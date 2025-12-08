<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CoursRepository;

class HomeStatController extends AbstractController
{
    #[Route('/home/stat', name: 'app_home_stat')]
    public function index(CoursRepository $coursRepository): Response
    {
        // Load some data to drive the UI
        $courses = $coursRepository->findBy([], ['id' => 'DESC'], 6);

        // Provide stats expected by the Twig template
        $stats = [
            'users_count' => 1250,
            'courses_count' => count($courses),
            'completed_quizzes' => 532,
            // Drive cards even if DB is empty (fallback to placeholders 1..3)
            'popular_courses' => count($courses) > 0 ? range(1, min(3, count($courses))) : [1, 2, 3],
            'instructors' => [1, 2, 3, 4],
            'testimonials' => [1, 2, 3, 4],
        ];

        return $this->render('front/home_stat/index.html.twig', [
            'controller_name' => 'HomeStatController',
            'stats' => $stats,
            'courses' => $courses,
        ]);
    }
}
