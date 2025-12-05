<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use App\Entity\Plan;
use App\Form\SubscriptionType;
use App\Repository\SubscriptionRepository;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/subscription')]
final class SubscriptionController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/', name: 'admin_subscription_index', methods: ['GET'])]
    public function index(SubscriptionRepository $subscriptionRepository, PlanRepository $planRepository): Response
    {
        // Par défaut, afficher les souscriptions actives
        $subscriptions = $subscriptionRepository->findAll();

        $plans = $planRepository->findAll();

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
            'plans' => $plans,
        ]);
    }

    #[Route('/status', name: 'admin_subscription_by_status', methods: ['GET'])]
    public function listByStatus(Request $request, SubscriptionRepository $subscriptionRepository): Response
    {
        $status = $request->query->get('status', 'active');

        $subscriptions = $subscriptionRepository->findByStatus((string) $status);

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/filter', name: 'admin_subscription_filter', methods: ['GET'])]
    public function filter(Request $request, SubscriptionRepository $subscriptionRepository, PlanRepository $planRepository): Response
    {
        $status = $request->query->get('status');
        $autoRenewParam = $request->query->get('autoRenew');
        $planId = $request->query->get('planId');

        $qb = $subscriptionRepository->createQueryBuilder('s');

        if ($status !== null && $status !== '') {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', (string) $status);
        }

        if ($autoRenewParam === '1') {
            $qb->andWhere('s.autoRenew = :autoRenew')
               ->setParameter('autoRenew', true);
        } elseif ($autoRenewParam === '2') {
            $qb->andWhere('s.autoRenew = :autoRenew')
               ->setParameter('autoRenew', false);
        }

        if ($planId) {
            $qb->andWhere('s.plan = :plan')
               ->setParameter('plan', (int) $planId);
        }

        $subscriptions = $qb
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();

        $plans = $planRepository->findAll();

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
            'plans' => $plans,
        ]);
    }

    #[Route('/active-auto-renew', name: 'admin_subscription_active_auto_renew', methods: ['GET'])]
    public function listActiveAutoRenewingByCurrentUser(SubscriptionRepository $subscriptionRepository): Response
    {
        $user = $this->getUser();

        $subscriptions = [];
        if ($user) {
            $subscriptions = $subscriptionRepository->findActiveAutoRenewingByUser($user);
        }

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }


    #[Route('/new', name: 'admin_subscription_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $subscription = new Subscription();
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($subscription);
            $this->em->flush();
            $this->addFlash('success', 'Subscription created');
            return $this->redirectToRoute('admin_subscription_index');
        }

        return $this->render('admin/subscription/new.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_subscription_show', methods: ['GET'])]
    public function show(Subscription $subscription, SubscriptionRepository $subscriptionRepository): Response
    {
        // Récupérer les souscriptions de l'utilisateur si disponible
        $userSubscriptions = [];
        if ($subscription->getUser()) {
            $userSubscriptions = $subscriptionRepository->findByUser($subscription->getUser());
        }

        // Vérifier si l'utilisateur a des souscriptions actives
        $hasActiveSubscriptions = $subscription->getUser() 
            ? $subscriptionRepository->findActiveByUser($subscription->getUser()) 
            : [];

        return $this->render('admin/subscription/show.html.twig', [
            'subscription' => $subscription,
            'userSubscriptions' => $userSubscriptions,
            'hasActiveSubscriptions' => count($hasActiveSubscriptions) > 0,
        ]);
    }
    #[Route('/{id}/edit', name: 'admin_subscription_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subscription $subscription): Response
    {
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Subscription updated');
            return $this->redirectToRoute('admin_subscription_index');
        }

        return $this->render('admin/subscription/edit.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_subscription_delete', methods: ['POST'])]
    public function delete(Request $request, Subscription $subscription): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subscription->getId(), $request->request->get('_token'))) {
            $this->em->remove($subscription);
            $this->em->flush();
            $this->addFlash('success', 'Subscription deleted');
        }

        return $this->redirectToRoute('admin_subscription_index');
    }
}
