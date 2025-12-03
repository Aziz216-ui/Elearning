<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Cours;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig', [
            'controller_name' => 'Dashboard<?php

namespace App\Entity;

use App\Repository\PanierRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Cours::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cours $cours = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(Cours $cours): self
    {
        $this->cours = $cours;
        return $this;
    }
}Controller',
        ]);
    }
}
