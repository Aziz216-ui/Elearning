<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Entity\ForulComment;
use App\Form\ForumPostType;
use App\Form\ForulCommentType;
use App\Repository\ForumPostRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/forum/post')]
class ForumPostController extends AbstractController
{
    // ---------------------- INDEX + SEARCH ----------------------
    #[Route('/', name: 'app_forum_post_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ForumPostRepository $forumPostRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $q = $request->query->get('q', '');
        $categorySlug = $request->query->get('category');

        // Filtrer les posts activés
        if ($q) {
            $forumPosts = $forumPostRepository->searchByTitleOrContent($q);
        } elseif ($categorySlug) {
            $forumPosts = $forumPostRepository->findByCategorySlug($categorySlug);
        } else {
            $forumPosts = $forumPostRepository->findEnabled();
        }

        return $this->render('forum_post/index.html.twig', [
            'forum_posts' => $forumPosts,
            'categories' => $categoryRepository->findAllOrderedByName(),
        ]);
    }

    // ---------------------- CREATE NEW POST ----------------------
    #[Route('/new', name: 'app_forum_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $forumPost = new ForumPost();
        $forumPost->setEnabled(true);

        $form = $this->createForm(ForumPostType::class, $forumPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'utilisateur connecté au post
            $forumPost->setUser($this->getUser());

            // Gestion de l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                try {
                    $imageFile->move($uploadDir, $newFilename);
                    $forumPost->setImage($newFilename);
                    $this->addFlash('success', 'Image téléchargée avec succès');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                }
            }

            $entityManager->persist($forumPost);
            $entityManager->flush();

            $this->addFlash('success', 'Post créé avec succès !');
            return $this->redirectToRoute('app_forum_post_index');
        }

        return $this->render('forum_post/new.html.twig', [
            'forum_post' => $forumPost,
            'form' => $form,
        ]);
    }

    // ---------------------- SHOW POST ----------------------
    #[Route('/{id}', name: 'app_forum_post_show', methods: ['GET'])]
    public function show(
        Request $request, 
        ForumPost $forumPost, 
        EntityManagerInterface $entityManager
    ): Response {
        // Debug: Afficher les informations de l'utilisateur et du post
        dump('Utilisateur connecté:', $this->getUser() ? $this->getUser()->getUserIdentifier() : 'non connecté');
        dump('Auteur du post:', $forumPost->getUser() ? $forumPost->getUser()->getUserIdentifier() : 'pas d\'auteur');
        
        // Vérifier si l'utilisateur peut voir ce post
        $this->denyAccessUnlessGranted('VIEW', $forumPost);

        // Ne compter qu'une vue par session
        $session = $request->getSession();
        $viewed = $session->get('viewed_posts', []);
        $postId = $forumPost->getId();

        if ($postId !== null && !in_array($postId, $viewed, true)) {
            $forumPost->incrementVues();
            $entityManager->flush();

            $viewed[] = $postId;
            $session->set('viewed_posts', $viewed);
        }

        return $this->render('forum_post/show.html.twig', [
            'forum_post' => $forumPost,
        ]);
    }

    // ---------------------- EDIT POST ----------------------
    #[Route('/{id}/edit', name: 'app_forum_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request, 
        ForumPost $forumPost, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // SÉCURITÉ : Vérifier que l'utilisateur peut modifier ce post
        $this->denyAccessUnlessGranted('EDIT', $forumPost);

        $form = $this->createForm(ForumPostType::class, $forumPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($forumPost->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/'.$forumPost->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads',
                        $newFilename
                    );
                    $forumPost->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Post modifié avec succès !');
            return $this->redirectToRoute('app_forum_post_index');
        }

        return $this->render('forum_post/edit.html.twig', [
            'forum_post' => $forumPost,
            'form' => $form,
        ]);
    }

    // ---------------------- DELETE POST ----------------------
    #[Route('/{id}/delete', name: 'app_forum_post_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Request $request, 
        ForumPost $forumPost, 
        EntityManagerInterface $entityManager
    ): Response {
        // SÉCURITÉ : Vérifier que l'utilisateur peut supprimer ce post
        $this->denyAccessUnlessGranted('DELETE', $forumPost);

        if ($this->isCsrfTokenValid('delete'.$forumPost->getId(), $request->request->get('_token'))) {
            // Supprimer l'image si elle existe
            if ($forumPost->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/'.$forumPost->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($forumPost);
            $entityManager->flush();

            $this->addFlash('success', 'Post supprimé avec succès !');
        }

        return $this->redirectToRoute('app_forum_post_index');
    }

    // ---------------------- LIKE POST ----------------------
    #[Route('/{id}/like', name: 'app_forum_post_like', methods: ['POST'])]
    public function like(
        Request $request,
        ForumPost $forumPost, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier si l'utilisateur a déjà liké (via session)
        $session = $request->getSession();
        $likedPosts = $session->get('liked_posts', []);
        $postId = $forumPost->getId();

        if (in_array($postId, $likedPosts)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà liké ce post'
            ], 400);
        }

        // Incrémenter les likes
        $forumPost->incrementLikes();
        $entityManager->flush();

        // Enregistrer dans la session
        $likedPosts[] = $postId;
        $session->set('liked_posts', $likedPosts);

        return new JsonResponse([
            'success' => true,
            'likes' => $forumPost->getLikes(),
            'message' => 'Post liké avec succès !'
        ]);
    }

    // ---------------------- UNLIKE POST ----------------------
    #[Route('/{id}/unlike', name: 'app_forum_post_unlike', methods: ['POST'])]
    public function unlike(
        Request $request,
        ForumPost $forumPost, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $session = $request->getSession();
        $likedPosts = $session->get('liked_posts', []);
        $postId = $forumPost->getId();

        if (!in_array($postId, $likedPosts)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas liké ce post'
            ], 400);
        }

        // Décrémenter les likes
        $currentLikes = $forumPost->getLikes() ?? 0;
        if ($currentLikes > 0) {
            $forumPost->setLikes($currentLikes - 1);
            $entityManager->flush();
        }

        // Retirer de la session
        $likedPosts = array_filter($likedPosts, fn($id) => $id !== $postId);
        $session->set('liked_posts', array_values($likedPosts));

        return new JsonResponse([
            'success' => true,
            'likes' => $forumPost->getLikes(),
            'message' => 'Like retiré'
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

    // ---------------------- TOGGLE ENABLE/DISABLE ----------------------
   #[Route('/admin/post/{id}/toggle', name: 'app_post_toggle', methods: ['POST','GET'])]
public function toggle(?ForumPost $post, EntityManagerInterface $em): Response
{
    if (!$post) {
        throw $this->createNotFoundException('Post introuvable');
    }

    $post->setEnabled(!$post->isEnabled());
    $em->flush();

    $this->addFlash('success', 'État du post modifié.');

    return $this->redirectToRoute('app_dashboard_forum');
}

}