<?php

namespace App\Controller\Front;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('front/dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/mes-cours', name: 'app_dashboard_cours')]
    public function myCourses(): Response
    {
        // TODO: Replace with real user courses listing
        return $this->render('front/dashboard/courses.html.twig');
    }

    #[Route('/detail', name: 'profile_app')]

    public function list(UserRepository $repository)
    {
        $users= $repository->findAll();
        return $this->render("author/listAuthors.html.twig",
            ["tabAuthors"=>$users]);
    }
}
