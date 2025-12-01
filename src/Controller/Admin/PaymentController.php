<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/payments')]
class PaymentController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'admin_payment_index', methods: ['GET'])]
    public function index(): Response
    {
        $payments = $this->entityManager->getRepository(Payment::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/payment/index.html.twig', [
            'payments' => $payments,
        ]);
    }
}
