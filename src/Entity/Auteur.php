<?php

namespace App\Entity;

use App\Repository\AuteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuteurRepository::class)]
class Auteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    // Spécialité / Domaine (ex: Développement Web, IA, ...)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialite = null;

    // Bio courte (facultatif)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    /**
     * @var Collection<int, Cours>
     */
    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'auteur', cascade: ['persist'])]
    private Collection $cours;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
    }

    // --- Getters / Setters ---
    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getSpecialite(): ?string { return $this->specialite; }
    public function setSpecialite(?string $specialite): static { $this->specialite = $specialite; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): static { $this->bio = $bio; return $this; }

    /** @return Collection<int, Cours> */
    public function getCours(): Collection { return $this->cours; }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setAuteur($this);
        }
        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            if ($cour->getAuteur() === $this) {
                $cour->setAuteur(null);
            }
        }
        return $this;
    }
}
