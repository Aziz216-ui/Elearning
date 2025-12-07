<?php

namespace App\Security\Voter;

use App\Entity\ForumPost;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ForumPostVoter extends Voter
{
    // Les attributs définis dans les annotations @IsGranted()
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // Si l'attribut n'est pas l'un de ceux que nous gérons, retourner false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // Ne voter que pour les objets ForumPost
        if (!$subject instanceof ForumPost) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté, sinon accès refusé
        if (!$user instanceof User) {
            return false;
        }

        // L'administrateur a tous les droits
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var ForumPost $post */
        $post = $subject;

        switch ($attribute) {
            case self::VIEW:
                // Tout le monde peut voir les posts activés
                // Seuls les auteurs et les admins peuvent voir les posts désactivés
                if ($post->isEnabled()) {
                    return true;
                }
                return $this->canEdit($post, $user);

            case self::EDIT:
                return $this->canEdit($post, $user);

            case self::DELETE:
                return $this->canDelete($post, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(ForumPost $post, User $user): bool
    {
        // L'auteur du post peut le modifier
        return $user === $post->getUser();
    }

    private function canDelete(ForumPost $post, User $user): bool
    {
        // L'auteur du post peut le supprimer
        return $user === $post->getUser();
    }
}
