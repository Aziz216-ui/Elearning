<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    // Crée un nouvel abonnement pour un utilisateur
     
    public function createSubscription(
        User $user, 
        Plan $plan
    ): Subscription {
        // Annuler l'ancien abonnement actif s'il existe
        $oldSubscription = $this->getActiveSubscription($user);
        if ($oldSubscription) {
            $this->cancelSubscription($oldSubscription);
        }

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStatus('active');
        $subscription->setStartDate(new \DateTime());
        $subscription->setAutoRenew(true);
        
        // Calculer la date de fin selon la durée
        if ($plan->getDuration() === 'monthly') {
            $endDate = (new \DateTime())->modify('+1 month');
        } elseif ($plan->getDuration() === 'yearly') {
            $endDate = (new \DateTime())->modify('+1 year');
        } else {
            $endDate = null; // Lifetime - pas de date de fin
        }
        $subscription->setEndDate($endDate);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    // Récupère l'abonnement actif d'un utilisateur
     
    public function getActiveSubscription(User $user): ?Subscription
    {
        return $this->entityManager->getRepository(Subscription::class)
            ->findOneBy([
                'user' => $user,
                'status' => 'active'
            ]);
    }

    //Annule un abonnement
    
    public function cancelSubscription(Subscription $subscription): void
    {
        // Désactiver le renouvellement automatique et marquer l'abonnement comme annulé
        $subscription->setAutoRenew(false);
        $subscription->setStatus('canceled');
        $subscription->setEndDate(new \DateTime());

        $this->entityManager->flush();
    }

    // Vérifie si un utilisateur a accès aux cours
     
    public function hasAccessToCourse(User $user): bool
    {
        $subscription = $this->getActiveSubscription($user);
        return $subscription && $subscription->isActive();
    }

    // Vérifie si l'utilisateur peut accéder à un certain nombre de cours
     
    public function canAccessCourse(User $user, int $courseCount = 1): bool
    {
        $subscription = $this->getActiveSubscription($user);
        
        if (!$subscription || !$subscription->isActive()) {
            return false;
        }

        $plan = $subscription->getPlan();
        $maxCourses = $plan->getMaxCourses();

        // Si maxCourses est null, accès illimité
        if ($maxCourses === null) {
            return true;
        }

        // Sinon vérifier le nombre de cours accessibles
        return $courseCount <= $maxCourses;
    }
}