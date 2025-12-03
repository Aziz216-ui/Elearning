<?php

namespace App\Controller;

use App\Entity\ForulComment;
use App\Form\ForulCommentType;
use App\Repository\ForulCommentRepository;
use App\Repository\ForumPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forul/comment')]
final class ForulCommentController extends AbstractController
{
    #[Route(name: 'app_forul_comment_index', methods: ['GET'])]
    public function index(ForulCommentRepository $forulCommentRepository): Response
    {
        return $this->render('forul_comment/index.html.twig', [
            'forul_comments' => $forulCommentRepository->findAll(),
        ]);
    }

    /**
     * Formulaire classique (page dédiée)
     */
    #[Route('/new', name: 'app_forul_comment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        ForumPostRepository $forumPostRepository
    ): Response {
        
        $postId = $request->query->get('post_id'); 
        $post = $forumPostRepository->find($postId);

        if (!$post) {
            throw $this->createNotFoundException('Post non trouvé');
        }

        $forulComment = new ForulComment();
        $forulComment->setUser($this->getUser());
        $forulComment->setPost($post);

        $form = $this->createForm(ForulCommentType::class, $forulComment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($forulComment);
            $entityManager->flush();

            return $this->redirectToRoute('app_forum_post_index');
        }

        return $this->render('forul_comment/new.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    /**
     * 🔥 Ajout de commentaire directement depuis la page SHOW du post
     */
    #[Route('/add/{postId}', name: 'app_forul_comment_add', methods: ['POST'])]
    public function addComment(
        Request $request,
        EntityManagerInterface $em,
        ForumPostRepository $postRepo,
        int $postId
    ): Response {
        $contenu = $request->request->get('contenu');

        if (!$contenu) {
            $this->addFlash('error', 'Le commentaire est vide.');
            return $this->redirectToRoute('app_forum_post_show', ['id' => $postId]);
        }

        $post = $postRepo->find($postId);

        if (!$post) {
            throw $this->createNotFoundException("Post introuvable");
        }

        $comment = new ForulComment();
        $comment->setContenu($contenu);
        $comment->setPost($post);

        if ($this->getUser()) {
            $comment->setUser($this->getUser());
        }

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès');

        return $this->redirectToRoute('app_forum_post_show', ['id' => $postId]);
    }

    #[Route('/{id}', name: 'app_forul_comment_show', methods: ['GET'])]
    public function show(ForulComment $forulComment): Response
    {
        return $this->render('forul_comment/show.html.twig', [
            'forul_comment' => $forulComment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_forul_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ForulComment $forulComment, EntityManagerInterface $entityManager): Response
    {
        try {
            // Vérifier que l'utilisateur est connecté
            if (!$this->getUser()) {
                if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->json([
                        'success' => false,
                        'message' => 'Vous devez être connecté pour modifier un commentaire.'
                    ], 403);
                }
                throw $this->createAccessDeniedException('Vous devez être connecté pour modifier un commentaire.');
            }

            // Vérifier que l'utilisateur est l'auteur du commentaire ou un admin
            if ($this->getUser() !== $forulComment->getUser() && !$this->isGranted('ROLE_ADMIN')) {
                if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->json([
                        'success' => false,
                        'message' => 'Vous n\'êtes pas autorisé à modifier ce commentaire.'
                    ], 403);
                }
                throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce commentaire');
            }

            // Vérifier si c'est une requête AJAX
            if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                $newContent = $request->request->get('contenu');
                
                if (empty($newContent)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Le commentaire ne peut pas être vide.'
                    ], 400);
                }
                
                $forulComment->setContenu($newContent);
                $forulComment->setUpdatedAtValue(); // Utilisation de la méthode de cycle de vie
                $entityManager->flush();
                
                return $this->json([
                    'success' => true,
                    'message' => 'Commentaire mis à jour avec succès',
                    'content' => nl2br(htmlspecialchars($newContent, ENT_QUOTES, 'UTF-8'))
                ]);
            }
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la mise à jour du commentaire: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }

        $form = $this->createForm(ForulCommentType::class, $forulComment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forulComment->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire mis à jour avec succès');
            return $this->redirectToRoute('app_forum_post_show', ['id' => $forulComment->getPost()->getId()]);
        }

        return $this->render('forul_comment/edit.html.twig', [
            'forul_comment' => $forulComment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_forul_comment_delete', methods: ['POST'])]
    public function delete(Request $request, ForulComment $forulComment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est l'auteur du commentaire ou un admin
        if ($this->getUser() !== $forulComment->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce commentaire');
        }

        $postId = $forulComment->getPost()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$forulComment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($forulComment);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé avec succès');
        }

        return $this->redirectToRoute('app_forum_post_show', ['id' => $postId]);
    }
}
