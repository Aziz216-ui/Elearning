<?php

namespace App\Controller;

use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {

        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class);
        $form->handleRequest($request);

        // Formulaire très simple : dès qu'il est soumis, on applique les changements
        if ($form->isSubmitted()) {
            // Récupérer les valeurs du formulaire en gardant l'existant si vide
            $email = $form->get('email')->getData() ?? $user->getEmail();
            $name = $form->get('name')->getData() ?? $user->getName();
            $lastname = $form->get('lastname')->getData() ?? $user->getLastname();
            $birthdate = $form->get('birthdate')->getData() ?? $user->getBirthdate();
            $sexe = $form->get('sexe')->getData() ?? $user->getSexe();

            $user->setEmail($email);
            $user->setName($name);
            $user->setLastname($lastname);
            $user->setBirthdate($birthdate);
            $user->setSexe($sexe);

            // Mot de passe uniquement si rempli
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashed = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashed);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/profile/return', name: 'app_profile_return')]
    public function returnToProfile(): Response
    {
        return $this->redirectToRoute('app_profile');
    }


}
