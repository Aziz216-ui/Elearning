<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test/compteur', name: 'test_compteur')]
    public function testCompteur(): Response
    {
        // Simuler des données de quiz
        $quizData = [
            'totalQuestions' => 10,
            'currentQuestion' => 5,
            'correctAnswers' => 3,
            'timeRemaining' => 300, // 5 minutes
        ];

        return $this->render('test/compteur.html.twig', [
            'quiz' => $quizData
        ]);
    }
}
