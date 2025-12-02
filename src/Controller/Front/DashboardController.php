<?php

namespace App\Controller\Front;

use App\Entity\Plan;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/dashboard/plans/filter', name: 'app_dashboard_plans_filter')]
    public function plansFilter(Request $request, PlanRepository $planRepository): Response
    {
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');

        if ($minPrice !== null && $minPrice !== '' || $maxPrice !== null && $maxPrice !== '') {
            // Filtrer par plage de prix si au moins une borne est fournie
            $plans = $planRepository->findByPriceRange($minPrice, $maxPrice);
        } else {
            // Sinon, tous les plans triés par prix
            $plans = $planRepository->findBy([], ['price' => 'ASC']);
        }

        return $this->render('front/dashboard/plans.html.twig', [
            'plans' => $plans,
        ]);
    }
}