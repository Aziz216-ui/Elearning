<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Entity\ForulComment;
use App\Form\ForumPostType;
use App\Form\ForulCommentType;
use App\Repository\ForumPostRepository;
use App\Repository\CategoryRepository;
use App\Service\HuggingFaceModerationService;
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
    private HuggingFaceModerationService $moderationService;

    public function __construct(HuggingFaceModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    // ---------------------- INDEX + SEARCH ----------------------
    #[Route('/', name: 'app_forum_post_index', methods: ['GET'])]
    public function index(
        Request $request,
        ForumPostRepository $forumPostRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $q = (string) $request->query->get('q', '');
        $categorySlug = $request->query->get('category');

        if ($q !== '') {
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

            // --- MODÉRATION IA (HuggingFace) ---
            $title = (string) $forumPost->getTitre();
            $content = (string) $forumPost->getContenu();
            $textToAnalyze = trim($title . ' ' . $content);

            // Variable pour suivre si on doit bloquer le post
            $shouldBlockPost = false;
            $blockReason = '';

            // Si le contenu n'est pas vide, on analyse
            if ($textToAnalyze !== '') {
                $analysis = $this->moderationService->analyzeContent($textToAnalyze);

                // Vérifier si l'analyse a réussi (pas d'erreur)
                if (isset($analysis['error']) && $analysis['error'] === true) {
                    // L'API de modération a échoué, mais on laisse passer le post
                    $this->addFlash('warning', '⚠️ La modération automatique est temporairement indisponible. Votre post sera vérifié manuellement.');
                } else {
                    // L'analyse a réussi, vérifier les résultats
                    if (isset($analysis['should_block']) && $analysis['should_block'] === true) {
                        $shouldBlockPost = true;
                        $blockReason = $analysis['moderation_reason'] ?? 'contenu bloqué';
                    }

                    if (isset($analysis['should_warn']) && $analysis['should_warn'] === true && !$shouldBlockPost) {
                        $reason = $analysis['moderation_reason'] ?? 'contenu engagé';
                        $this->addFlash('warning', '⚠️ Avertissement IA : ' . $reason);
                    }
                }
            }

            // Si le contenu doit être bloqué, on arrête ici
            if ($shouldBlockPost) {
                $this->addFlash('error', '❌ Votre post contient du contenu inapproprié : ' . $blockReason);
                // On retourne le formulaire avec les données saisies
                return $this->render('forum_post/new.html.twig', [
                    'forum_post' => $forumPost,
                    'form' => $form,
                ]);
            }
            // ---------------------------------------

            // Gestion de l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';

                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                try {
                    $imageFile->move($uploadDir, $newFilename);
                    $forumPost->setImage($newFilename);
                    $this->addFlash('success', 'Image téléchargée avec succès');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                    // On continue quand même, l'image n'est pas obligatoire
                }
            }

            // ✅ PERSISTANCE ET FLUSH - Uniquement si le post est valide
            try {
                $entityManager->persist($forumPost);
                $entityManager->flush();

                $this->addFlash('success', 'Post créé avec succès !');
                return $this->redirectToRoute('app_forum_post_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du post : ' . $e->getMessage());
                return $this->render('forum_post/new.html.twig', [
                    'forum_post' => $forumPost,
                    'form' => $form,
                ]);
            }
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
        $this->denyAccessUnlessGranted('VIEW', $forumPost);

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
        $this->denyAccessUnlessGranted('EDIT', $forumPost);

        $form = $this->createForm(ForumPostType::class, $forumPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // --- MODÉRATION IA (HuggingFace) ---
            $title = (string) $forumPost->getTitre();
            $content = (string) $forumPost->getContenu();
            $textToAnalyze = trim($title . ' ' . $content);

            $shouldBlockPost = false;
            $blockReason = '';

            if ($textToAnalyze !== '') {
                $analysis = $this->moderationService->analyzeContent($textToAnalyze);

                // Vérifier si l'analyse a réussi (pas d'erreur)
                if (isset($analysis['error']) && $analysis['error'] === true) {
                    $this->addFlash('warning', '⚠️ La modération automatique est temporairement indisponible. Votre modification sera vérifiée manuellement.');
                } else {
                    if (isset($analysis['should_block']) && $analysis['should_block'] === true) {
                        $shouldBlockPost = true;
                        $blockReason = $analysis['moderation_reason'] ?? 'contenu bloqué';
                    }

                    if (isset($analysis['should_warn']) && $analysis['should_warn'] === true && !$shouldBlockPost) {
                        $reason = $analysis['moderation_reason'] ?? 'contenu engagé';
                        $this->addFlash('warning', '⚠️ Avertissement IA : ' . $reason);
                    }
                }
            }

            // Si le contenu doit être bloqué, on arrête ici
            if ($shouldBlockPost) {
                $this->addFlash('error', '❌ Modification interdite : contenu inapproprié : ' . $blockReason);
                return $this->render('forum_post/edit.html.twig', [
                    'forum_post' => $forumPost,
                    'form' => $form,
                ]);
            }
            // ---------------------------------------

            // Gestion de l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($forumPost->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $forumPost->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads', $newFilename);
                    $forumPost->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }
            }

            // ✅ FLUSH - Sauvegarder les modifications
            try {
                $entityManager->flush();

                $this->addFlash('success', 'Post modifié avec succès !');
                return $this->redirectToRoute('app_forum_post_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du post : ' . $e->getMessage());
                return $this->render('forum_post/edit.html.twig', [
                    'forum_post' => $forumPost,
                    'form' => $form,
                ]);
            }
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
        $this->denyAccessUnlessGranted('DELETE', $forumPost);

        if ($this->isCsrfTokenValid('delete' . $forumPost->getId(), $request->request->get('_token'))) {
            if ($forumPost->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $forumPost->getImage();
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
        $session = $request->getSession();
        $likedPosts = $session->get('liked_posts', []);
        $postId = $forumPost->getId();

        if (in_array($postId, $likedPosts, true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà liké ce post'
            ], 400);
        }

        $forumPost->incrementLikes();
        $entityManager->flush();

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

        if (!in_array($postId, $likedPosts, true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas liké ce post'
            ], 400);
        }

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