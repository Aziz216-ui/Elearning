<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Repository\CoursRepository;
use App\Entity\Subscription;
use App\Entity\Payment;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SessionInterface $session, CoursRepository $coursRepository): Response
    {
        $cart = $session->get('cart', []); // [id => qty]
        $items = [];
        $total = 0.0;

        if (!empty($cart)) {
            $coursList = $coursRepository->findBy(['id' => array_keys($cart)]);
            foreach ($coursList as $cours) {
                $qty = $cart[$cours->getId()] ?? 0;
                $lineTotal = ($cours->getPrice() ?? 0) * $qty;
                $items[] = [
                    'cours' => $cours,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
                $total += $lineTotal;
            }
        }

        return $this->render('front/cart/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/add/{id}', name: 'add', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function add(Cours $cours, SessionInterface $session, Request $request): RedirectResponse
    {
        $cart = $session->get('cart', []);
        $id = $cours->getId();
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);

        $this->addFlash('success', 'Cours ajouté au panier.');

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'remove', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function remove(Cours $cours, SessionInterface $session, Request $request): RedirectResponse
    {
        $cart = $session->get('cart', []);
        $id = $cours->getId();
        if (isset($cart[$id])) {
            unset($cart[$id]);
            $session->set('cart', $cart);
            $this->addFlash('info', 'Cours retiré du panier.');
        }
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_cart_index');
    }

    #[Route('/decrement/{id}', name: 'decrement', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function decrement(Cours $cours, SessionInterface $session): RedirectResponse
    {
        $cart = $session->get('cart', []);
        $id = $cours->getId();
        if (isset($cart[$id])) {
            $cart[$id] = max(0, $cart[$id] - 1);
            if ($cart[$id] === 0) {
                unset($cart[$id]);
            }
            $session->set('cart', $cart);
            $this->addFlash('info', 'Quantité mise à jour.');
        }
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'clear', methods: ['POST','GET'])]
    public function clear(SessionInterface $session): RedirectResponse
    {
        $session->set('cart', []);
        $this->addFlash('warning', 'Panier vidé.');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/checkout', name: 'checkout', methods: ['GET'])]
    public function checkout(SessionInterface $session, CoursRepository $coursRepository): Response
    {
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0.0;

        if (!empty($cart)) {
            $coursList = $coursRepository->findBy(['id' => array_keys($cart)]);
            foreach ($coursList as $cours) {
                $qty = $cart[$cours->getId()] ?? 0;
                $lineTotal = ($cours->getPrice() ?? 0) * $qty;
                $items[] = [
                    'cours' => $cours,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
                $total += $lineTotal;
            }
        }

        if (empty($items)) {
            $this->addFlash('info', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour continuer le paiement.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/cart/checkout.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/pay', name: 'pay', methods: ['POST'])]
    public function pay(Request $request, SessionInterface $session, CoursRepository $coursRepository, SubscriptionRepository $subscriptionRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour effectuer le paiement.');
            return $this->redirectToRoute('app_login');
        }

        $verificationCode = $request->request->get('verification_code');
        if ($verificationCode !== '1234') {
            $this->addFlash('error', 'Code de vérification invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_cart_checkout');
        }

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('info', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        $coursList = $coursRepository->findBy(['id' => array_keys($cart)]);
        $user = $this->getUser();

        $now = new \DateTime();
        $endDate = (clone $now)->modify('+1 month');

        foreach ($coursList as $cours) {
            // Créer un abonnement spécifique à ce cours (valide 1 mois)
            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setCours($cours);
            $subscription->setStatus('active');
            $subscription->setStartDate($now);
            $subscription->setEndDate($endDate);
            $subscription->setAutoRenew(false);

            $entityManager->persist($subscription);

            // Enregistrer un paiement lié à cette subscription
            $payment = new Payment();
            $payment->setUser($user);
            $payment->setSubscription($subscription);
            $payment->setAmount((string) ($cours->getPrice() ?? 0));
            $payment->setCurrency('TND');
            $payment->setStatus('completed');
            $payment->setCreatedAt(new \DateTime());

            $entityManager->persist($payment);
        }

        $entityManager->flush();

        // Vider le panier de session après paiement
        $session->set('cart', []);

        $this->addFlash('success', 'Paiement réussi. Les cours ont été ajoutés à vos cours.');

        return $this->redirectToRoute('app_my_courses');
    }
}