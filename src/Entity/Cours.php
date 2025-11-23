<?php

namespace App\Entity;

    use App\Repository\CoursRepository;
    use Doctrine\DBAL\Types\Types;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity(repositoryClass: CoursRepository::class)]
    class Cours
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;

        #[ORM\Column(length: 255)]
        private ?string $title = null;

        #[ORM\Column(type: Types::TEXT)]
        private ?string $description = null;

        #[ORM\Column]
        private ?float $price = null;
        #[ORM\Column(type: "datetime_immutable", nullable: true)]
        private ?\DateTimeImmutable $duration = null;
        
        

        #[ORM\Column(length: 255)]
        private ?string $category = null;

        #[ORM\Column]
        private ?bool $isPublished = null;
        #[ORM\ManyToOne(inversedBy: 'cours')]
        #[ORM\JoinColumn(nullable: false)]
        private ?Auteur $auteur = null;

        public function getId(): ?int
        {
            return $this->id;
        }

        public function getTitle(): ?string
        {
            return $this->title;
        }

        public function setTitle(string $title): static
        {
            $this->title = $title;

            return $this;
        }

        public function getDescription(): ?string
        {
            return $this->description;
        }

        public function setDescription(string $description): static
        {
            $this->description = $description;

            return $this;
        }

        public function getPrice(): ?float
        {
            return $this->price;
        }

        public function setPrice(float $price): static
        {
            $this->price = $price;

            return $this;
        }

        public function getCategory(): ?string
        {
            return $this->category;
        }

        public function setCategory(?string $category): static
        {
            $this->category = $category;

            return $this;
        }
        public function getDuration(): ?\DateTimeImmutable
        {
            return $this->duration;
        }
        
        public function setDuration(?\DateTimeImmutable $duration): static
        {
            $this->duration = $duration;
        
            return $this;
        }
        

        public function isPublished(): ?bool
        {
            return $this->isPublished;
        }

        public function setIsPublished(bool $isPublished): static
        {
            $this->isPublished = $isPublished;

            return $this;
        }

        public function getAuteur(): ?Auteur
        {
            return $this->auteur;
        }

        public function setAuteur(?Auteur $auteur): static
        {
            $this->auteur = $auteur;

            return $this;
        }
    }
