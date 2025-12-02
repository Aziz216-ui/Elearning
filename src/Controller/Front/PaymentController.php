<?php

namespace App\Controller\Front;

use App\Entity\Plan;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use App\Service\PaymeeService;
use App\Service\SubscriptionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private PaymeeService $paymeeService,
        private SubscriptionManager $subscriptionManager,
        private EntityManagerInterface $entityManager,
        private PlanRepository $planRepository,
        private SubscriptionRepository $subscriptionRepository,
    ) {}

    #[Route('', name: 'payment_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('payment_plans');
    }

    // Affiche la page des plans d'abonnement
     
    #[Route('/plans', name: 'payment_plans')]
    public function plans(): Response
    {
        // Plans actifs triés par prix via le repository dédié
        $plans = $this->planRepository->findAllActiveByPrice();

        return $this->render('front/payment/plans.html.twig', [
            'plans' => $plans,
        ]);
    }

    // Affiche la page de détails de paiement pour un plan donné
    
    #[Route('/checkout/{id}', name: 'payment_checkout')]
    public function checkout(Plan $plan): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/payment/checkout.html.twig', [
            'plan' => $plan,
        ]);
    }

    // Crée une session de paiement Paymee et redirige vers Paymee
    
    #[Route('/paymee/{id}', name: 'payment_paymee')]
    public function paymee(Plan $plan): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $redirectUrl = $this->paymeeService->createTransferCheckout(
                $plan,
                $this->getUser(),
                $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            return $this->redirect($redirectUrl);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création de la session de paiement.');
            return $this->redirectToRoute('payment_plans');
        }
    }

    // Affiche l'abonnement actuel de l'utilisateur
     
    #[Route('/my-subscription', name: 'payment_my_subscription')]
    public function mySubscription(): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour voir votre abonnement.');
            return $this->redirectToRoute('payment_plans');
        }

        $subscriptions = $this->subscriptionRepository->findByUser($this->getUser());

        return $this->render('front/payment/my_subscription.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/my-subscription/filter', name: 'payment_my_subscription_filter')]
    public function mySubscriptionFilter(Request $request): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour voir votre abonnement.');
            return $this->redirectToRoute('payment_plans');
        }

        $statusFilter = $request->query->get('status');

        if ($statusFilter === 'active') {
            // Abonnements actifs uniquement
            $subscriptions = $this->subscriptionRepository->findActiveByUser($this->getUser());
        } elseif ($statusFilter === 'canceled') {
            // Abonnements inactifs uniquement
            $subscriptions = $this->subscriptionRepository->findInactiveByUser($this->getUser());
        } else {
            // Tous les abonnements de l'utilisateur
            $subscriptions = $this->subscriptionRepository->findByUser($this->getUser());
        }

        return $this->render('front/payment/my_subscription.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }

    // Désactive le renouvellement automatique pour un abonnement donné

    #[Route('/cancel-subscription/{id}', name: 'payment_cancel_subscription', methods: ['POST'])]
    public function cancelSubscription(\App\Entity\Subscription $subscription): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour annuler votre abonnement.');
            return $this->redirectToRoute('payment_plans');
        }

        // Sécurité : s'assurer que l'abonnement appartient bien à l'utilisateur connecté
        if ($subscription->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Cet abonnement ne vous appartient pas.');
            return $this->redirectToRoute('payment_my_subscription');
        }

        if ($subscription->isActive() && $subscription->isAutoRenew()) {
            $this->subscriptionManager->cancelSubscription($subscription);
            $this->addFlash('success', 'Le renouvellement automatique a été désactivé.');
        } else {
            $this->addFlash('error', 'Cet abonnement n\'est pas actif ou ne se renouvelle pas automatiquement.');
        }

        return $this->redirectToRoute('payment_my_subscription');
    }

    #[Route('/cancel-subscription-full/{id}', name: 'payment_cancel_subscription_full', methods: ['POST'])]
    public function cancelSubscriptionFull(\App\Entity\Subscription $subscription): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour annuler votre abonnement.');
            return $this->redirectToRoute('payment_plans');
        }

        // Sécurité : s'assurer que l'abonnement appartient bien à l'utilisateur connecté
        if ($subscription->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Cet abonnement ne vous appartient pas.');
            return $this->redirectToRoute('payment_my_subscription');
        }

        // Annuler totalement l'abonnement : désactiver l'auto-renouvellement et marquer comme annulé
        $subscription->setAutoRenew(false);
        $subscription->setStatus('canceled');
        $subscription->setEndDate(new \DateTime());

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre abonnement a été annulé.');

        return $this->redirectToRoute('payment_my_subscription');
    }
}