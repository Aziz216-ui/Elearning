<?php

namespace App\Entity;

use App\Repository\ForulCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\ForumPost;
use App\Entity\User;

#[ORM\Entity(repositoryClass: ForulCommentRepository::class)]
class ForulComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $contenu = null;

    // -----------------------
    // Relation ManyToOne -> ForumPost
    // -----------------------
    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ForumPost $post = null;

    // -----------------------
    // Relation ManyToOne -> User
    // -----------------------
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // -----------------------
    // Getters & Setters
    // -----------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getPost(): ?ForumPost
    {
        return $this->post;
    }

    public function setPost(?ForumPost $post): static
    {
        $this->post = $post;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
