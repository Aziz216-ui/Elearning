<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Entity\Auteur;
use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        // Ici tu peux afficher l'utilisateur connecté
        $user = $this->getUser();

        return $this->render('Front/home/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/home/courses', name: 'app_home_courses')]
    #[Route('/home/courses.html', name: 'app_home_courses_html')]
    public function courses(Request $request, CoursRepository $coursRepository): Response
    {
        $currentCategory = $request->query->get('category');
        $currentCategory = ($currentCategory !== null && $currentCategory !== '') ? (string)$currentCategory : null;

        // Fetch categories and build two datasets without UI buttons
        $distinctCategories = array_map(static fn($r) => $r['category'], $coursRepository->findDistinctCategories());

        // Determine which list to show based on 'type' filter
        $type = strtolower((string) ($request->query->get('type') ?? 'all'));
        $type = in_array($type, ['all', 'new', 'popular'], true) ? $type : 'all';

        // Map type to sorting
        $sortField = 'price';
        $sortOrder = 'ASC';
        if ($type === 'new') {
            $sortField = 'id';
            $sortOrder = 'DESC';
        } elseif ($type === 'popular') {
            // Using price DESC as a proxy for popularity
            $sortField = 'price';
            $sortOrder = 'DESC';
        }

        $cours = $coursRepository->findAdvanced(
            $currentCategory,
            null,
            null,
            null,
            null,
            null,
            null,
            $sortField,
            $sortOrder
        );

        return $this->render('Front/home/courses.html.twig', [
            'cours' => $cours,
            'distinctCategories' => $distinctCategories,
            'currentCategory' => $currentCategory,
            'currentType' => $type,
        ]);
    }

    #[Route('/home/courses/{id}', name: 'app_home_course_show', methods: ['GET'])]
    public function show(Cours $cour): Response
    {
        return $this->render('Front/home/course_show.html.twig', [
            'cour' => $cour,
        ]);
    }

    #[Route('/home/auteurs/{id}', name: 'app_home_author_show', methods: ['GET'])]
    public function authorShow(Auteur $auteur): Response
    {
        return $this->render('Front/home/author_show.html.twig', [
            'auteur' => $auteur,
        ]);
    }
}
