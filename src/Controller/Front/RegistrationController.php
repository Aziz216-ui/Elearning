<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Log form errors in dev if needed
        if ($form->isSubmitted() && ! $form->isValid()) {
            foreach ($form->getErrors(true, true) as $e) {
                // écrit dans le logger PHP / dev log
                error_log('[FORM ERROR] ' . ($e->getOrigin()?->getName() ?? 'form') . ' -> ' . $e->getMessage());
            }
        }



        if ($form->isSubmitted() && $form->isValid()) {
            // hash password
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            // Ensure required fields (NOT NULL in DB) have values
            // roles JSON NOT NULL
            if (method_exists($user, 'getRoles') && empty($user->getRoles())) {
                $user->setRoles(['ROLE_USER']);
            }
            // is_verified TINYINT(1) NOT NULL
            if (method_exists($user, 'isVerified') && method_exists($user, 'setIsVerified')) {
                // Default to false on registration
                $user->setIsVerified(false);
            }
            // created_at DATETIME NOT NULL
            if (method_exists($user, 'getCreatedAt') && method_exists($user, 'setCreatedAt') && null === $user->getCreatedAt()) {
                $user->setCreatedAt(new \DateTimeImmutable());
            }

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Throwable $e) {
                // Surface DB errors to the UI to aid debugging
                $this->addFlash('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
                // Re-render the form with error messages
                return $this->render('front/registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                    ->from(new \Symfony\Component\Mime\Address('melkimohamedaziz1@gmail.com', 'Elearning Access'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre email')
                    ->htmlTemplate('emails/comfirmation_email.html.twig')
            );

            $this->addFlash('success', 'Votre compte a été créé avec succès. Veuillez vérifier votre email pour le confirmer.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        // L'utilisateur doit être connecté pour confirmer son adresse
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();

            // Pour débogage si besoin (optionnel) : écrire dans var dir
            // \file_put_contents(__DIR__ . '/../../var/debug_verify.txt', sprintf("verify user=%s\n", $user?->getEmail() ?? 'n/a'), FILE_APPEND);

            $this->emailVerifier->handleEmailConfirmation($request, $user);

            $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès !');
            return $this->redirectToRoute('app_dashboard');
        } catch (VerifyEmailExceptionInterface $exception) {
            $message = $translator->trans($exception->getReason(), [], 'VerifyEmailBundle');
            $this->addFlash('error', 'Erreur de vérification : ' . $message);

            return $this->redirectToRoute('app_register');
        }
    }
}