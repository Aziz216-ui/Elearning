<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Repository\CoursRepository;
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
}
