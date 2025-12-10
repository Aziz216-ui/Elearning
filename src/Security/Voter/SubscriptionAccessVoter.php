<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\SubscriptionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SubscriptionAccessVoter extends Voter
{
    const ACCESS_CONTENT = 'ACCESS_CONTENT';

    public function __construct(private SubscriptionManager $subscriptionManager)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter supporte l'attribut 'ACCESS_CONTENT' pour n'importe quel objet
        return $attribute === self::ACCESS_CONTENT;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof User) {
            return false;
        }

        // Vérifier si l'utilisateur a un abonnement actif
        return $this->subscriptionManager->hasAccessToCourse($user);
    }
}