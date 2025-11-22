<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Entity\Panier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CoursController extends AbstractController
{
    #[Route('/dashboard/cours', name: 'app_dashboard_cours')]
    public function index(EntityManagerInterface $em): Response
    {
        $cours = $em->getRepository(Cours::class)->findAll();

        return $this->render('front/cours/index.html.twig', [
            'cours' => $cours
        ]);
    }

    #[Route('front/dashboard/panier/add/{id}', name: 'app_add_panier')]
    public function addToPanier(Cours $cours, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $panier = new Panier();
        $panier->setUser($user);
        $panier->setCours($cours);

        $em->persist($panier);
        $em->flush();

        $this->addFlash('success', 'Cours ajouté au panier !');

        return $this->redirectToRoute('app_dashboard_cours');
    }
}