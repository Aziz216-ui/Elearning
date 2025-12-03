<?php

namespace App\Controller\Front;

use App\Entity\Auteur;
use App\Form\AuteurType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AuteurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/auteur')]
final class AuteurController extends AbstractController
{
    #[Route(name: 'app_auteur_index', methods: ['GET'])]
    public function index(Request $request, AuteurRepository $auteurRepository): Response
    {
        $q = $request->query->get('q');
        $specialite = $request->query->get('specialite');
        $minCourses = $request->query->get('minCourses');
        $minCourses = ($minCourses !== null && $minCourses !== '') ? (int)$minCourses : null;

        // Base list according to filters
        if ($q) {
            $auteurs = $auteurRepository->searchByKeyword($q);
        } elseif ($specialite) {
            $auteurs = $auteurRepository->listBySpecialite($specialite);
        } elseif ($minCourses !== null) {
            $auteurs = array_map(static fn($row) => $row['auteur'], $auteurRepository->authorsWithAtLeast($minCourses));
        } else {
            $auteurs = $auteurRepository->findBy([], ['nom' => 'ASC']);
        }

        $topAuthors = $auteurRepository->topAuthors(5);
        // Build a map auteurId => nbCours for fast display
        $countsRows = $auteurRepository->findAuteursWithCoursCount();
        $countsByAuteurId = [];
        foreach ($countsRows as $row) {
            // $row contains ['auteur' => Auteur, 'nbCours' => string|int]
            $countsByAuteurId[$row['auteur']->getId()] = (int)$row['nbCours'];
        }

        return $this->render('auteur/index.html.twig', [
            'auteurs' => $auteurs,
            'topAuthors' => $topAuthors,
            'countsByAuteurId' => $countsByAuteurId,
        ]);
    }

    #[Route('/new', name: 'app_auteur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $auteur = new Auteur();
        $form = $this->createForm(AuteurType::class, $auteur);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle photo upload if provided
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                $newFilename = bin2hex(random_bytes(8)).'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move($this->getParameter('auteur_upload_dir'), $newFilename);
                    $auteur->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Upload de la photo échoué.');
                }
            }
            foreach ($auteur->getCours() as $cour) {
                // sécurité: assurer l'association côté cours
                $cour->setAuteur($auteur);
            }
        
            $entityManager->persist($auteur);
            $entityManager->flush();
            $this->addFlash('success', 'Auteur créé avec succès.');
        
            return $this->redirectToRoute('app_auteur_index');
        }

        return $this->render('auteur/new.html.twig', [
            'auteur' => $auteur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_auteur_show', methods: ['GET'])]
    public function show(Auteur $auteur, AuteurRepository $auteurRepository): Response
    {
        $totalCours = $auteurRepository->totalCoursesForAuteur($auteur->getId());
        $hasCourses = $auteurRepository->hasCourses($auteur->getId());
        $topAuthors = $auteurRepository->topAuthors(5);

        return $this->render('auteur/show.html.twig', [
            'auteur' => $auteur,
            'totalCours' => $totalCours,
            'hasCourses' => $hasCourses,
            'topAuthors' => $topAuthors,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_auteur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Auteur $auteur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AuteurType::class, $auteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle new photo upload if provided
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                $newFilename = bin2hex(random_bytes(8)).'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move($this->getParameter('auteur_upload_dir'), $newFilename);
                    $auteur->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Upload de la photo échoué.');
                }
            }
            // Synchroniser l'association côté Cours (ajouts via le formulaire)
            // Note: la suppression/détachement de cours n'est pas autorisée car Cours.auteur est non-nullable
            foreach ($auteur->getCours() as $cour) {
                $cour->setAuteur($auteur);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Auteur mis à jour avec succès.');

            return $this->redirectToRoute('app_auteur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('auteur/edit.html.twig', [
            'auteur' => $auteur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_auteur_delete', methods: ['POST'])]
    public function delete(Request $request, Auteur $auteur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$auteur->getId(), $request->getPayload()->getString('_token'))) {
            // Empêcher la suppression d'un auteur tant qu'il possède des cours
            if ($auteur->getCours()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer cet auteur: des cours lui sont encore associés. Réassignez ou supprimez les cours d\'abord.');
                return $this->redirectToRoute('app_auteur_show', ['id' => $auteur->getId()]);
            }
            $entityManager->remove($auteur);
            $entityManager->flush();
            $this->addFlash('success', 'Auteur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_auteur_index', [], Response::HTTP_SEE_OTHER);
    }
}
