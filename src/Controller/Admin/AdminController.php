<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard/index.html.twig', [
            'users' => $userRepository->listUserByName()
        ]);
    }
    #[Route('/admin/users', name: 'admin_user_index', methods: ['GET'])]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/admin/user/{id}/courses', name: 'admin_user_courses', methods: ['GET'])]
    public function listCoursesByUser($id, UserRepository $userRepository): Response
    {
        $courses = $userRepository->showAllCoursesByUser((int) $id);

        // Debug: Afficher les cours récupérés
        dump($courses);

        return $this->render('admin/listCoursesByUser.html.twig', [
            'tab' => $courses,
            'userId' => $id
        ]);
    }

    #[Route('/admin/user/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        // Laisser UserType gérer les groupes de validation (Default + Registration pour la création)
        // Activer les groupes de validation 'Default' et 'Registration' pour imposer les contraintes d'entité
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false,
            'validation_groups' => ['Default', 'Registration'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le mot de passe en clair depuis le formulaire
            $plainPassword = $form->get('plainPassword')->getData();

            // Ne hacher que si le mot de passe est fourni et non vide
            if (is_string($plainPassword) && $plainPassword !== '') {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } else {
                // Ajout d'une erreur de formulaire si le mot de passe est manquant
                $form->get('plainPassword')->addError(new \Symfony\Component\Form\FormError('Le mot de passe est obligatoire.'));
                // Renvoyer le formulaire avec les erreurs affichées
                return $this->render('admin/user/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Sauvegarder les valeurs initiales pour faire des mises à jour partielles
        $originalData = [
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'lastname' => $user->getLastname(),
            'birthdate' => $user->getBirthdate(),
            'sexe' => $user->getSexe(),
        ];

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pour chaque champ, si l'admin a soumis une valeur vide (ou null),
            // on restaure la valeur originale pour éviter d'écraser accidentellement.
            $email = $form->get('email')->getData();
            if ($email === null || $email === '') {
                $user->setEmail($originalData['email']);
            }

            $name = $form->get('name')->getData();
            if ($name === null || $name === '') {
                $user->setName($originalData['name']);
            }

            $lastname = $form->get('lastname')->getData();
            if ($lastname === null || $lastname === '') {
                $user->setLastname($originalData['lastname']);
            }

            $birthdate = $form->get('birthdate')->getData();
            if ($birthdate === null || $birthdate === '') {
                $user->setBirthdate($originalData['birthdate']);
            }

            $sexe = $form->get('sexe')->getData();
            if ($sexe === null || $sexe === '') {
                $user->setSexe($originalData['sexe']);
            }

            // Mot de passe : si fourni, hacher et mettre à jour; sinon ne pas changer
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                );
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {

            try {
                $entityManager->remove($user);
                $entityManager->flush();

                $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer : cet utilisateur a des données liées (panier, commande...).');
            }
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/admin/users/search', name: 'admin_user_search')]
    public function search(Request $request, UserRepository $repo): Response
    {
        $term = trim((string) $request->query->get('q', ''));

        if ($term === '') {
            // Si le champ de recherche est vide, afficher la liste complète
            $users = $repo->findAll();
        } else {
            $users = $repo->findUserByName($term);
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'users' => $users,
            'term' => $term
        ]);
    }

}
