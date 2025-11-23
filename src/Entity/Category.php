<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icone = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, ForumPost>
     */
    #[ORM\OneToMany(targetEntity: ForumPost::class, mappedBy: 'category')]
    private Collection $forumPosts;

    public function __construct()
    {
        $this->forumPosts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): static
    {
        $this->icone = $icone;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, ForumPost>
     */
    public function getForumPosts(): Collection
    {
        return $this->forumPosts;
    }

    public function addForumPost(ForumPost $forumPost): static
    {
        if (!$this->forumPosts->contains($forumPost)) {
            $this->forumPosts->add($forumPost);
            $forumPost->setCategory($this);
        }

        return $this;
    }

    public function removeForumPost(ForumPost $forumPost): static
    {
        if ($this->forumPosts->removeElement($forumPost)) {
            if ($forumPost->getCategory() === $this) {
                $forumPost->setCategory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}