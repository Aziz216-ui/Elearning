<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CoursRepository;
use App\Entity\Cours;
use App\Entity\Auteur;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('front/home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/home/courses', name: 'app_home_courses')]
    #[Route('/home/courses.html', name: 'app_home_courses_html')]
    public function courses(CoursRepository $coursRepository): Response
    {
        $cours = $coursRepository->findAll();
        return $this->render('front/home/courses.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/home/courses/{id}', name: 'app_home_course_show', methods: ['GET'])]
    public function show(Cours $cour): Response
    {
        return $this->render('front/home/course_show.html.twig', [
            'cour' => $cour,
        ]);
    }

    #[Route('/home/auteurs/{id}', name: 'app_home_author_show', methods: ['GET'])]
    public function authorShow(Auteur $auteur): Response
    {
        return $this->render('front/home/author_show.html.twig', [
            'auteur' => $auteur,
        ]);
    }
}
