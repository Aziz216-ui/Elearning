<?php

namespace App\Controller\front;

use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {

        $user = $this->getUser();

        return $this->render('front/profile/index.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();

        $form = $this->createForm(RegistrationFormType::class, $user);

        // Ajout des champs de mot de passe au formulaire
        $form->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Les mots de passe doivent correspondre.',
            'options' => ['attr' => ['class' => 'form-control']],
            'required' => false,
            'first_options'  => ['label' => 'Nouveau mot de passe'],
            'second_options' => ['label' => 'Confirmer le mot de passe'],
            'mapped' => false,
            'constraints' => [
                new Length([
                    'min' => 6,
                    'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                    'max' => 4096,
                ]),
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour du mot de passe si fourni
            if (!empty($form->get('plainPassword')->getData())) {
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('front/profile/edit.html.twig', [
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
