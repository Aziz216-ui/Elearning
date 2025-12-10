<?php

namespace App\Security\Voter;

use App\Entity\ForumPost;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ForumPostVoter extends Voter
{
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';
    const VIEW = 'VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter gère seulement ces attributs pour les objets ForumPost
        if (!in_array($attribute, [self::EDIT, self::DELETE, self::VIEW])) {
            return false;
        }

        if (!$subject instanceof ForumPost) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof User) {
            return false;
        }

        /** @var ForumPost $post */
        $post = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($post, $user),
            self::EDIT => $this->canEdit($post, $user),
            self::DELETE => $this->canDelete($post, $user),
            default => false,
        };
    }

    private function canView(ForumPost $post, User $user): bool
    {
        // Tout le monde peut voir un post activé
        if ($post->isEnabled()) {
            return true;
        }

        // Seul l'auteur peut voir son propre post désactivé
        return $this->isOwner($post, $user);
    }

    private function canEdit(ForumPost $post, User $user): bool
    {
        // Seul l'auteur peut modifier son post
        // Ou un admin si vous avez un rôle ROLE_ADMIN
        return $this->isOwner($post, $user) || $this->isAdmin($user);
    }

    private function canDelete(ForumPost $post, User $user): bool
    {
        // Seul l'auteur peut supprimer son post
        // Ou un admin si vous avez un rôle ROLE_ADMIN
        return $this->isOwner($post, $user) || $this->isAdmin($user);
    }

    private function isOwner(ForumPost $post, User $user): bool
    {
        return $post->getUser() === $user;
    }

    private function isAdmin(User $user): bool
    {
        // Vérifiez si l'utilisateur a le rôle ROLE_ADMIN
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}