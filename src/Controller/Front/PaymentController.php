<?php

namespace App\Controller\Front;

use App\Entity\Plan;
use App\Entity\Payment;
use App\Entity\User;
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
        private EntityManagerInterface $entityManager
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
        $plans = $this->entityManager->getRepository(Plan::class)
            ->findBy(['isActive' => true], ['price' => 'ASC']);

        return $this->render('front/payment/plans.html.twig', [
            'plans' => $plans,
        ]);
    }

    // Crée une session de paiement et redirige vers PayMee
     
    #[Route('/checkout/{id}', name: 'payment_checkout')]
    public function checkout(Plan $plan): Response
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

        // Récupérer tous les abonnements de l'utilisateur (du plus récent au plus ancien)
        $subscriptions = $this->entityManager->getRepository(\App\Entity\Subscription::class)
            ->findBy(['user' => $this->getUser()], ['startDate' => 'DESC']);

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
}