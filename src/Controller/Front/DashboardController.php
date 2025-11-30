<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Entity\Panier;
use App\Repository\CoursRepository;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(CoursRepository $coursRepository, PanierRepository $panierRepository): Response
    {
        $user = $this->getUser();
        $cours = $coursRepository->findAll();
        $panier = $panierRepository->findUserPanier($user);
        $coursInPanier = [];
        
        foreach ($panier as $item) {
            $coursInPanier[] = $item->getCours()->getId();
        }

        return $this->render('dashboard/index.html.twig', [
            'cours' => $cours,
            'coursInPanier' => $coursInPanier,
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouterAuPanier(Cours $cours, EntityManagerInterface $entityManager, PanierRepository $panierRepository): Response
    {
        $user = $this->getUser();
        
        // Vérifier si le cours est déjà dans le panier
        if ($panierRepository->isCourseInUserPanier($user, $cours)) {
            $this->addFlash('warning', 'Ce cours est déjà dans votre panier.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Ajouter le cours au panier
        $panier = new Panier();
        $panier->setUser($user);
        $panier->setCours($cours);
        
        $entityManager->persist($panier);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le cours a été ajouté à votre panier avec succès.');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/mes-cours', name: 'app_mes_cours')]
    public function mesCours(PanierRepository $panierRepository): Response
    {
        $user = $this->getUser();
        $panier = $panierRepository->findUserPanier($user);
        
        return $this->render('dashboard/mes_cours.html.twig', [
            'panier' => $panier,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_show', methods: ['GET'])]
    public function showCours(Cours $cours): Response
    {
        return $this->render('dashboard/cours_show.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/detail', name: 'profile_app')]
    public function list(UserRepository $repository)
    {
        $users= $repository->findAll();
        return $this->render("author/listAuthors.html.twig",
            ["tabAuthors"=>$users]);
    }
}
