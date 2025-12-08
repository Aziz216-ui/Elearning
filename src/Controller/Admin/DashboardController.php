<?php

namespace App\Controller\Admin;

use App\Repository\ForumPostRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        public function forum(Request $request, ForumPostRepository $forumPostRepository, CategoryRepository $categoryRepository, UserRepository $userRepository): Response
        {
            $categoryId = $request->query->get('category');
            $userId = $request->query->get('user');

            if ($categoryId) {
                $category = $categoryRepository->find($categoryId);
                $forumPosts = $category ? $forumPostRepository->findByCategory($category) : $forumPostRepository->findAll();
            } elseif ($userId) {
                $user = $userRepository->find($userId);
                $forumPosts = $user ? $forumPostRepository->findByUser($user) : $forumPostRepository->findAll();
            } else {
                $forumPosts = $forumPostRepository->findAll();
            }

            $mostLiked = $forumPostRepository->findMostLiked(1);
            $mostCommented = $forumPostRepository->findMostCommented(1);

            return $this->render('admin/dashboard/admin.html.twig', [
                'forum_posts' => $forumPosts,
                'categories' => $categoryRepository->findAllOrderedByName(),
                'users' => $userRepository->findAll(),
                'mostLiked' => $mostLiked,
                'mostCommented' => $mostCommented,
            ]);
        }
    }