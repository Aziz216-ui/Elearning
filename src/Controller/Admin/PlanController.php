<?php

namespace App\Controller\Admin;

use App\Entity\Plan;
use App\Form\PlanType;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/plan')]
final class PlanController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

     #[Route('/', name: 'admin_plan_index', methods: ['GET'])]
    public function index(Request $request, PlanRepository $planRepository): Response
    {
        $active = $request->query->get('active');

        if ($active === '1') {
            $plans = $planRepository->findActivePlans();
        } elseif ($active === '0') {
            $plans = $planRepository->findInactivePlans();
        } else {
            $plans = $planRepository->findAll();
        }

        return $this->render('admin/plan/index.html.twig', [
            'plans' => $plans,
        ]);
    }

    #[Route('/price-range', name: 'admin_plan_price_range', methods: ['GET'])]
    public function listByPriceRange(Request $request, PlanRepository $planRepository): Response
    {
        // Filtrer par prix min/max en utilisant le repository
        $min = $request->query->get('min');
        $max = $request->query->get('max');
        $minPrice = ($min !== null && $min !== '') ? (string) $min : null;
        $maxPrice = ($max !== null && $max !== '') ? (string) $max : null;

        $plans = $planRepository->findByPriceRange($minPrice, $maxPrice);

        return $this->render('admin/plan/index.html.twig', [
            'plans' => $plans,
        ]);
    }


    #[Route('/new', name: 'admin_plan_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $plan = new Plan();
        $form = $this->createForm(PlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($plan);
            $this->em->flush();
            $this->addFlash('success', 'Plan created');
            return $this->redirectToRoute('admin_plan_index');
        }

        return $this->render('admin/plan/new.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_plan_show', methods: ['GET'])]
    public function show(Request $request, Plan $plan, SubscriptionRepository $subscriptionRepository): Response
    {
        $status = $request->query->get('status');
        $qb = $subscriptionRepository->createQueryBuilder('s')
            ->andWhere('s.plan = :plan')
            ->setParameter('plan', $plan);

        if ($status !== null && $status !== '') {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', (string) $status);
        }

        $subscriptions = $qb
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/plan/show.html.twig', [
            'plan' => $plan,
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_plan_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Plan $plan): Response
    {
        $form = $this->createForm(PlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Plan updated');
            return $this->redirectToRoute('admin_plan_index');
        }

        return $this->render('admin/plan/edit.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_plan_delete', methods: ['POST'])]
    public function delete(Request $request, Plan $plan): Response
    {
        if ($this->isCsrfTokenValid('delete'.$plan->getId(), $request->request->get('_token'))) {
            $this->em->remove($plan);
            $this->em->flush();
            $this->addFlash('success', 'Plan deleted');
        }

        return $this->redirectToRoute('admin_plan_index');
    }
}
