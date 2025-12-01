<?php

namespace App\Controller\Front;

use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('front/dashboard/index.html.twig');
    }

    #[Route('/dashboard/plans', name: 'app_dashboard_plans')]
    public function plans(EntityManagerInterface $entityManager): Response
    {
        $plans = $entityManager->getRepository(Plan::class)
            ->findBy(['isActive' => true], ['price' => 'ASC']);

        return $this->render('front/dashboard/plans.html.twig', [
            'plans' => $plans,
        ]);
    }
}