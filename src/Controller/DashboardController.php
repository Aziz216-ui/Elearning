<?php

namespace App\Controller;

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
        $panier = $user ? $panierRepository->findUserPanier($user) : [];
        $coursInPanier = [];
        
        foreach ($panier as $item) {
            $coursInPanier[] = $item->getCours()->getId();
        }

        return $this->render('dashboard/index.html.twig', [
            'cours' => $cours,
            'coursInPanier' => $coursInPanier,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_detail', methods: ['GET'])]
    public function coursDetail(Cours $cours): Response
    {
        $addedOn = new \DateTimeImmutable('2025-11-18');

        $stats = [
            ['label' => 'Leçons', 'value' => 12],
            ['label' => 'Durée totale', 'value' => '8h'],
            ['label' => 'Exercices', 'value' => 24],
            ['label' => 'Projets', 'value' => 3],
        ];

        $objectives = [
            'Les fondamentaux de PHP : syntaxe, variables, et types de données',
            'Les structures de contrôle (conditions, boucles)',
            'Les fonctions et la programmation orientée objet',
            'La manipulation de formulaires et la gestion des données',
            "L'interaction avec les bases de données MySQL",
            'La création de sessions et la gestion des cookies',
            'Les bonnes pratiques de sécurité en PHP',
            "Le développement d'une application web complète",
        ];

        $modules = [
            [
                'title' => 'Module 1 : Introduction et Installation',
                'duration' => '45 min',
                'lessons' => "3 leçons • Installation de l'environnement de développement",
            ],
            [
                'title' => 'Module 2 : Syntaxe de base PHP',
                'duration' => '1h 30min',
                'lessons' => '4 leçons • Variables, opérateurs, et types de données',
            ],
            [
                'title' => 'Module 3 : Structures de contrôle',
                'duration' => '1h 15min',
                'lessons' => '3 leçons • Conditions, boucles, et switch',
            ],
            [
                'title' => 'Module 4 : Fonctions et tableaux',
                'duration' => '2h',
                'lessons' => '5 leçons • Création et utilisation de fonctions',
            ],
            [
                'title' => 'Module 5 : Programmation Orientée Objet',
                'duration' => '2h 30min',
                'lessons' => '6 leçons • Classes, objets, héritage, et encapsulation',
            ],
            [
                'title' => 'Module 6 : Projet Final',
                'duration' => '1h',
                'lessons' => '1 projet • Application web complète',
            ],
        ];

        $requirements = [
            [
                'title' => '💻 Connaissances de base',
                'description' => 'HTML et CSS recommandés mais non obligatoires',
            ],
            [
                'title' => '🛠️ Logiciels requis',
                'description' => 'Éditeur de code (VS Code recommandé)',
            ],
            [
                'title' => '⚙️ Environnement',
                'description' => 'Serveur local (XAMPP, WAMP ou MAMP)',
            ],
        ];

        $badges = [
            ['label' => 'Débutant', 'modifier' => ''],
            ['label' => 'PHP', 'modifier' => 'intermediate'],
            ['label' => 'Programmation Web', 'modifier' => ''],
        ];

        $meta = [
            ['icon' => '📅', 'text' => 'Ajouté le : '.$addedOn->format('d/m/Y')],
            ['icon' => '⏱️', 'text' => 'Durée : 8 heures'],
            ['icon' => '📚', 'text' => '12 leçons'],
        ];

        $description = "Ce cours complet vous permettra d'apprendre les bases de PHP et de créer vos premières pages dynamiques. PHP est un langage de programmation côté serveur largement utilisé pour le développement web. Vous découvrirez comment créer des sites web interactifs, gérer des bases de données, et construire des applications web professionnelles.";

        return $this->render('dashboard/course_detail.html.twig', [
            'cours' => $cours,
            'addedOn' => $addedOn,
            'stats' => $stats,
            'objectives' => $objectives,
            'modules' => $modules,
            'requirements' => $requirements,
            'badges' => $badges,
            'meta' => $meta,
            'description' => $description,
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouterAuPanier(Cours $cours, EntityManagerInterface $entityManager, PanierRepository $panierRepository): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un cours à votre panier.');
            return $this->redirectToRoute('app_login');
        }
        
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
        $panier = $user ? $panierRepository->findUserPanier($user) : [];
        
        return $this->render('dashboard/mes_cours.html.twig', [
            'panier' => $panier,
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
