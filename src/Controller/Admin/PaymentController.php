<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use App\Entity\User;

use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/payments')]
class PaymentController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'admin_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository): Response
    {
        $payments = $paymentRepository->findAll();
        $topPlans = $paymentRepository->findTopPlansByPayments(3);
        $topCourses = $paymentRepository->findTopCoursesByPayments(3);

        return $this->render('admin/payment/index.html.twig', [
            'payments' => $payments,
            'topPlans' => $topPlans,
            'topCourses' => $topCourses,
        ]);
    }

    #[Route('/date-range', name: 'admin_payment_date_range', methods: ['GET'])]
    public function listByDateRange(Request $request, PaymentRepository $paymentRepository): Response
    {
        $dmin = $request->query->get('dmin');
        $dmax = $request->query->get('dmax');
        $dateMin = ($dmin !== null && $dmin !== '') ? new \DateTime($dmin) : null;
        $dateMax = ($dmax !== null && $dmax !== '') ? new \DateTime($dmax) : null;

        $payments = $paymentRepository->findByDateRange($dateMin, $dateMax);

        return $this->render('admin/payment/index.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/user', name: 'admin_payment_user', methods: ['GET'])]
    public function listByUser(Request $request, PaymentRepository $paymentRepository): Response
    {
        $email = $request->query->get('userEmail');
        $payments = [];

        if ($email !== null && $email !== '') {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $payments = $paymentRepository->findByUser($user);
            }
        }

        return $this->render('admin/payment/index.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/amount-range', name: 'admin_payment_amount_range', methods: ['GET'])]
    public function listByAmountRange(Request $request, PaymentRepository $paymentRepository): Response
    {
        $min = $request->query->get('min');
        $max = $request->query->get('max');
        $minAmount = ($min !== null && $min !== '') ? (string) $min : null;
        $maxAmount = ($max !== null && $max !== '') ? (string) $max : null;

        $payments = $paymentRepository->findByAmountRange($minAmount, $maxAmount);

        return $this->render('admin/payment/index.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/status', name: 'admin_payment_status', methods: ['GET'])]
    public function listByStatusForCurrentUser(Request $request, PaymentRepository $paymentRepository): Response
    {
        $user = $this->getUser();
        $status = $request->query->get('status');

        if ($status === null || $status === '') {
            // "Tous" : afficher tous les paiements (même résultat que l'index)
            $payments = $paymentRepository->findAll();
        } elseif ($user) {
            // Filtrer par statut pour l'utilisateur courant
            $payments = $paymentRepository->findByUserAndStatus($user, (string) $status);
        } else {
            $payments = [];
        }

        return $this->render('admin/payment/index.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/export/csv', name: 'admin_payment_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, PaymentRepository $paymentRepository): Response
    {
        $dmin = $request->query->get('dmin');
        $dmax = $request->query->get('dmax');
        $min = $request->query->get('min');
        $max = $request->query->get('max');
        $status = $request->query->get('status');

        if ($dmin || $dmax) {
            $dateMin = ($dmin !== null && $dmin !== '') ? new \DateTime($dmin) : null;
            $dateMax = ($dmax !== null && $dmax !== '') ? new \DateTime($dmax) : null;
            $payments = $paymentRepository->findByDateRange($dateMin, $dateMax);
        } elseif ($min !== null || $max !== null) {
            $minAmount = ($min !== null && $min !== '') ? (string) $min : null;
            $maxAmount = ($max !== null && $max !== '') ? (string) $max : null;
            $payments = $paymentRepository->findByAmountRange($minAmount, $maxAmount);
        } elseif ($status !== null && $status !== '') {
            // Pour l'export, on ne limite pas à l'utilisateur courant
            $payments = $paymentRepository->findBy(['status' => (string) $status]);
        } else {
            $payments = $paymentRepository->findAll();
        }

        $rows = [];
        $rows[] = ['ID', 'Utilisateur', 'Abonnement', 'Montant', 'Status', 'Créé le'];

        foreach ($payments as $payment) {
            $rows[] = [
                $payment->getId(),
                $payment->getUser() ? $payment->getUser()->getEmail() : '',
                $payment->getSubscription() ? $payment->getSubscription()->getId() : '',
                $payment->getAmount(),
                $payment->getStatus(),
                $payment->getCreatedAt() ? $payment->getCreatedAt()->format('Y-m-d H:i') : '',
            ];
        }

        $fh = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($fh, $row, ';');
        }
        rewind($fh);
        $csvContent = stream_get_contents($fh);
        fclose($fh);

        return new Response(
            $csvContent,
            200,
            [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="payments.csv"',
            ]
        );
    }

    #[Route('/export/pdf', name: 'admin_payment_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, PaymentRepository $paymentRepository): Response
    {
        $dmin = $request->query->get('dmin');
        $dmax = $request->query->get('dmax');
        $min = $request->query->get('min');
        $max = $request->query->get('max');
        $status = $request->query->get('status');

        if ($dmin || $dmax) {
            $dateMin = ($dmin !== null && $dmin !== '') ? new \DateTime($dmin) : null;
            $dateMax = ($dmax !== null && $dmax !== '') ? new \DateTime($dmax) : null;
            $payments = $paymentRepository->findByDateRange($dateMin, $dateMax);
        } elseif ($min !== null || $max !== null) {
            $minAmount = ($min !== null && $min !== '') ? (string) $min : null;
            $maxAmount = ($max !== null && $max !== '') ? (string) $max : null;
            $payments = $paymentRepository->findByAmountRange($minAmount, $maxAmount);
        } elseif ($status !== null && $status !== '') {
            $payments = $paymentRepository->findBy(['status' => (string) $status]);
        } else {
            $payments = $paymentRepository->findAll();
        }

        return $this->render('admin/payment/export_pdf.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/{id}', name: 'admin_payment_show', methods: ['GET'])]
    public function show(Payment $payment, PaymentRepository $paymentRepository): Response
    {
        // Récupérer tous les paiements de l'utilisateur
        $userPayments = [];
        if ($payment->getUser()) {
            $userPayments = $paymentRepository->findByUser($payment->getUser());
        }

        return $this->render('admin/payment/show.html.twig', [
            'payment' => $payment,
            'userPayments' => $userPayments,
        ]);
    }
}