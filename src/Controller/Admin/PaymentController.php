<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
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

        return $this->render('admin/payment/index.html.twig', [
            'payments' => $payments,
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