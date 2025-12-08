<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cours')]
final class CoursController extends AbstractController
{
    #[Route(name: 'app_cours_index', methods: ['GET'])]
    #[Route(path: '', name: 'app_home_courses', methods: ['GET'])]
    public function index(Request $request, CoursRepository $coursRepository): Response
    {
        $category = $request->query->get('category');
        $category = ($category !== null && $category !== '') ? (string)$category : null;

        $min = $request->query->get('min');
        $max = $request->query->get('max');
        $minF = ($min !== null && $min !== '') ? (float)$min : null;
        $maxF = ($max !== null && $max !== '') ? (float)$max : null;

        $keyword = $request->query->get('q');
        $keyword = ($keyword !== null && $keyword !== '') ? (string)$keyword : null;

        $publishedParam = $request->query->get('published'); // '1' | '0' | '' | null
        $published = ($publishedParam === null || $publishedParam === '') ? null : ($publishedParam === '1');

        $dmin = $request->query->get('dmin'); // ex: 2025-01-01
        $dmax = $request->query->get('dmax');
        $durationMin = ($dmin !== null && $dmin !== '') ? new \DateTimeImmutable($dmin) : null;
        $durationMax = ($dmax !== null && $dmax !== '') ? new \DateTimeImmutable($dmax) : null;

        $sort = strtoupper($request->query->get('sort', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $sortField = $request->query->get('sortField', 'price');

        $cours = $coursRepository->findAdvanced(
            $category,
            $minF,
            $maxF,
            $keyword,
            $published,
            $durationMin,
            $durationMax,
            $sortField,
            $sort
        );
        $range = $coursRepository->getMinMaxPrice($category);
        $distinctCategories = array_map(static fn($r) => $r['category'], $coursRepository->findDistinctCategories());
        $statsByCategory = $coursRepository->countByCategory();

        return $this->render('cours/index.html.twig', [
            'cours' => $cours,
            'minPrice' => $range['minPrice'] ?? null,
            'maxPrice' => $range['maxPrice'] ?? null,
            'distinctCategories' => $distinctCategories,
            'statsByCategory' => $statsByCategory,
        ]);
    }

    #[Route('/new', name: 'app_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cour);
            $entityManager->flush();

            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/new.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cours_show', methods: ['GET'])]
    public function show(Cours $cour, CoursRepository $coursRepository): Response
    {
        $latest = $coursRepository->findLatest(5);
        $hasPublishedByAuteur = $cour->getAuteur() ? $coursRepository->hasPublishedCourses($cour->getAuteur()->getId()) : false;

        return $this->render('cours/show.html.twig', [
            'cour' => $cour,
            'latest' => $latest,
            'hasPublishedByAuteur' => $hasPublishedByAuteur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('cours/edit.html.twig', [
            'cour' => $cour,
            'form' => $form->createView(),
        ]);
    }
    

    #[Route('/{id}', name: 'app_cours_delete', methods: ['POST'])]
    public function delete(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cour->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($cour);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
    }
}
