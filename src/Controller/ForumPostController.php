<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Entity\ForulComment;
use App\Form\ForumPostType;
use App\Form\ForulCommentType;
use App\Repository\ForumPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forum/post')]
class ForumPostController extends AbstractController
{
    // ---------------------- INDEX + SEARCH ----------------------
    #[Route('/', name: 'app_forum_post_index', methods: ['GET', 'POST'])]
    public function index(Request $request, ForumPostRepository $forumPostRepository, EntityManagerInterface $entityManager): Response
    {
        $q = $request->query->get('q', '');

        // 🔥 Filtrer uniquement les posts activés
        if ($q) {
            $forumPosts = $forumPostRepository->searchByTitleOrContent($q);
        } else {
            $forumPosts = $forumPostRepository->findEnabled();
        }

        $commentForms = [];

        foreach ($forumPosts as $post) {
            $comment = new ForulComment();
            $comment->setPost($post);
            $comment->setUser($this->getUser());

            $form = $this->createForm(ForulCommentType::class, $comment, [
                'action' => $this->generateUrl('app_forum_post_index'),
                'method' => 'POST'
            ]);

            $commentForms[$post->getId()] = $form->createView();
        }

        // ---- Ajouter un commentaire ----
        if ($request->isMethod('POST')) {
            $postId = $request->request->get('postId');
            $post = $forumPostRepository->findEnabledPosts($postId);

            // 🔥 Empêcher d’ajouter un commentaire à un post désactivé
            if ($post && $post->isEnabled()) {
                $comment = new ForulComment();
                $comment->setPost($post);
                $comment->setUser($this->getUser());

                $form = $this->createForm(ForulCommentType::class, $comment);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager->persist($comment);
                    $entityManager->flush();
                }
            }

            return $this->redirectToRoute('app_forum_post_index');
        }

        return $this->render('forum_post/index.html.twig', [
            'forum_posts' => $forumPosts,
            'commentForms' => $commentForms,
        ]);
    }

    // ---------------------- CREATE NEW POST ----------------------
    #[Route('/new', name: 'app_forum_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $forumPost = new ForumPost();
        $forumPost->setEnabled(true); // un nouveau post est activé par défaut

        $form = $this->createForm(ForumPostType::class, $forumPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                try {
                    $imageFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    $forumPost->setImage($newFilename);
                    
                    // Debug
                    $this->addFlash('info', 'Image téléchargée avec succès : '.$newFilename);
                    $this->addFlash('info', 'Chemin complet : '.$uploadDir.'/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image : '.$e->getMessage());
                }
            } else {
                // Utiliser l'image par défaut si aucune image n'est téléchargée
                $forumPost->setImage('elearning.png');
            }
            
            $forumPost->setUser($this->getUser());
            $entityManager->persist($forumPost);
            $entityManager->flush();

            return $this->redirectToRoute('app_forum_post_index');
        }

        return $this->render('forum_post/new.html.twig', [
            'forum_post' => $forumPost,
            'form' => $form,
        ]);
    }

    // ---------------------- ADMIN DASHBOARD ----------------------
    #[Route('/admin', name: 'app_forum_post_admin', methods: ['GET'])]
    public function admin(ForumPostRepository $forumPostRepository): Response
    {
        $forumPosts = $forumPostRepository->findAll();

        return $this->render('forum_post/admin.html.twig', [
            'forum_posts' => $forumPosts,
        ]);
    }

    // 🔥 Toggle Enable/Disable d’un post
    #[Route('/admin/post/{id}/toggle', name: 'app_post_toggle')]
    public function toggle(ForumPost $post, EntityManagerInterface $em): Response
    {
        $post->setEnabled(!$post->isEnabled());
        $em->flush();

        $this->addFlash('success', 'État du post mis à jour.');

        return $this->redirectToRoute('app_forum_post_admin');
    }

    // ---------------------- EDIT POST ----------------------
    #[Route('/{id}/edit', name: 'app_forum_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ForumPost $forumPost, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ForumPostType::class, $forumPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_forum_post_index');
        }

        return $this->render('forum_post/edit.html.twig', [
            'forum_post' => $forumPost,
            'form' => $form,
        ]);
    }

    // ---------------------- DELETE POST ----------------------
    #[Route('/{id}/delete', name: 'app_forum_post_delete', methods: ['POST'])]
    public function delete(Request $request, ForumPost $forumPost, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$forumPost->getId(), $request->request->get('_token'))) {
            $entityManager->remove($forumPost);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_forum_post_index');
    }

    // ---------------------- SHOW POST ----------------------
    #[Route('/{id}', name: 'app_forum_post_show', methods: ['GET'])]
    public function show(ForumPost $forumPost): Response
    {
        return $this->render('forum_post/show.html.twig', [
            'forum_post' => $forumPost,
        ]);
    }
}
