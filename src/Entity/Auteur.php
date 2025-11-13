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

    /**
     * @var Collection<int, Cours>
     */
    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'Auteur')]
    private Collection $bio;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'auteurs')]
    private ?self $Cours = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'Cours')]
    private Collection $auteurs;

    public function __construct()
    {
        $this->bio = new ArrayCollection();
        $this->auteurs = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getBio(): Collection
    {
        return $this->bio;
    }

    public function addBio(Cours $bio): static
    {
        if (!$this->bio->contains($bio)) {
            $this->bio->add($bio);
            $bio->setAuteur($this);
        }

        return $this;
    }

    public function removeBio(Cours $bio): static
    {
        if ($this->bio->removeElement($bio)) {
            // set the owning side to null (unless already changed)
            if ($bio->getAuteur() === $this) {
                $bio->setAuteur(null);
            }
        }

        return $this;
    }

    public function getCours(): ?self
    {
        return $this->Cours;
    }

    public function setCours(?self $Cours): static
    {
        $this->Cours = $Cours;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getAuteurs(): Collection
    {
        return $this->auteurs;
    }

    public function addAuteur(self $auteur): static
    {
        if (!$this->auteurs->contains($auteur)) {
            $this->auteurs->add($auteur);
            $auteur->setCours($this);
        }

        return $this;
    }

    public function removeAuteur(self $auteur): static
    {
        if ($this->auteurs->removeElement($auteur)) {
            // set the owning side to null (unless already changed)
            if ($auteur->getCours() === $this) {
                $auteur->setCours(null);
            }
        }

        return $this;
    }
}
