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

#[Route('/favorites', name: 'app_fav_')]
class FavoriteController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SessionInterface $session, CoursRepository $coursRepository): Response
    {
        $favs = $session->get('favs', []); // array of course IDs
        $items = [];
        if (!empty($favs)) {
            $items = $coursRepository->findBy(['id' => $favs]);
        }
        return $this->render('front/favorites/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/toggle/{id}', name: 'toggle', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function toggle(Cours $cours, SessionInterface $session, Request $request): RedirectResponse
    {
        $favs = $session->get('favs', []);
        $id = $cours->getId();
        if (in_array($id, $favs, true)) {
            $favs = array_values(array_filter($favs, fn ($v) => (int)$v !== (int)$id));
            $this->addFlash('info', 'Cours retiré des favoris.');
        } else {
            $favs[] = $id;
            $favs = array_values(array_unique($favs));
            $this->addFlash('success', 'Cours ajouté aux favoris.');
        }
        $session->set('favs', $favs);
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_fav_index');
    }

    #[Route('/clear', name: 'clear', methods: ['GET','POST'])]
    public function clear(SessionInterface $session): RedirectResponse
    {
        $session->set('favs', []);
        $this->addFlash('warning', 'Favoris vidés.');
        return $this->redirectToRoute('app_fav_index');
    }
}
