<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Entity\Auteur;
use App\Repository\CoursRepository;
use App\Repository\SubscriptionRepository;
use Knp\Component\Pager\PaginatorInterface;
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
        ]);
    }

    #[Route('/home/courses', name: 'app_home_courses')]
    #[Route('/home/courses.html', name: 'app_home_courses_html')]
    public function courses(Request $request, CoursRepository $coursRepository, PaginatorInterface $paginator): Response
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

        $coursQuery = $coursRepository->findAdvanced(
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

        $pagination = $paginator->paginate(
            $coursQuery,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('Front/home/courses.html.twig', [
            'cours' => $pagination,
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

    #[Route('/home/my-courses', name: 'app_my_courses', methods: ['GET'])]
    public function myCourses(SubscriptionRepository $subscriptionRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Must be authenticated
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Subscriptions based on plans
        $subscriptions = $subscriptionRepository->findActiveByUser($user);

        $byId = [];
        foreach ($subscriptions as $sub) {
            $plan = $sub->getPlan();
            if (!$plan) { continue; }
            foreach ($plan->getCourses() as $course) {
                $byId[$course->getId()] = $course;
            }
        }

        // Subscriptions based on single courses
        $courseSubscriptions = $subscriptionRepository->findActiveCourseSubscriptionsByUser($user);
        foreach ($courseSubscriptions as $sub) {
            $course = $sub->getCours();
            if ($course) {
                $byId[$course->getId()] = $course;
            }
        }

        $cours = array_values($byId);

        // Build distinct categories from merged set
        $distinctCategories = [];
        foreach ($cours as $c) {
            $cat = $c->getCategory();
            if ($cat !== null && $cat !== '' && !in_array($cat, $distinctCategories, true)) {
                $distinctCategories[] = $cat;
            }
        }

        $pagination = $paginator->paginate(
            $cours,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('Front/home/courses.html.twig', [
            'cours' => $pagination,
            'distinctCategories' => $distinctCategories,
            'currentCategory' => null,
            'currentType' => 'my',
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
