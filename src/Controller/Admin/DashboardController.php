<?php

namespace App\Controller\Admin;

use App\Repository\ForumPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Cours;

final class DashboardController extends AbstractController
    {
        // Route pour la page principale du dashboard
        #[Route('/dashboard', name: 'app_dashboard')]
        public function index(): Response
        {
            return $this->render('admin/dashboard/index.html.twig', [
                'controller_name' => 'Dashboard',
            ]);
        }
    
        // Route pour la page du forum dans le dashboard
        #[Route('/dashboard/forum', name: 'app_dashboard_forum')]
        public function forum(ForumPostRepository $forumPostRepository): Response
        {
            $forumPosts = $forumPostRepository->findAll();
    
            return $this->render('admin/dashboard/admin.html.twig', [
                'forum_posts' => $forumPosts,
            ]);
        }
    }
    
    
