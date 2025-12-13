<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private $logger;

    public function __construct(
        private EmailVerifier $emailVerifier,
        \Psr\Log\LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new \Symfony\Component\HttpKernel\Log\Logger();
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Encode le mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();

                // Envoi de l'email de confirmation
                $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address(
                            $this->getParameter('app.mailer_from_email'),
                            $this->getParameter('app.mailer_from_name')
                        ))
                        ->to($user->getEmail())
                        ->subject('Veuillez confirmer votre email')
                        // Utiliser le template présent dans templates/emails/comfirmation_email.html.twig
                        ->htmlTemplate('emails/comfirmation_email.html.twig')
                );

                $this->addFlash('success', 'Un email de confirmation a été envoyé à ' . $user->getEmail() . '. Veuillez vérifier votre boîte de réception.');

                // Ne pas connecter automatiquement l'utilisateur : rediriger vers la page de login
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email de confirmation : ' . $e->getMessage());

                // Enregistrement de l'erreur dans les logs
                $this->logger->error('Erreur lors de l\'inscription : ' . $e->getMessage(), [
                    'email' => $user->getEmail(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->redirectToRoute('app_register'); // l'emplacement de l'enregistrement
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        // Permettre la vérification sans que l'utilisateur soit connecté : récupérer l'id depuis la query
        $id = $request->query->get('id');

        if (null === $id) {
            // Rediriger vers la page de login (au lieu de register)
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id);
        if (null === $user) {
            // Rediriger vers la page de login (au lieu de register)
            return $this->redirectToRoute('app_login');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);

            $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès ! Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');

        } catch (VerifyEmailExceptionInterface $exception) {
            $errorMessage = $translator->trans($exception->getReason(), [], 'VerifyEmailBundle');
            $this->addFlash('error', 'Erreur de vérification : ' . $errorMessage);

            // En cas d'erreur de validation du lien, rediriger vers la page de login
            return $this->redirectToRoute('app_login');
        }
    }
}
