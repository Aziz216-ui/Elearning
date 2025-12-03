<?php

namespace App\Controller\Admin;

use App\Repository\ForumPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ForumPostRepository $forumPostRepository): Response
    {
        $forumPosts = $forumPostRepository->findAll();
        
        return $this->render('admin/dashboard/admin.html.twig', [
            'forum_posts' => $forumPosts,
        ]);
    }
}
