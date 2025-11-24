<?php
namespace App\Controller\admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/admin/users/stats', name: 'admin_user_stats')]
    public function index(UserRepository $userRepository): Response
    {
        $year = (int) date('Y'); // année en cours

        $usersByMonth = $userRepository->countUsersByMonth($year);

        // Pour s'assurer que tous les mois apparaissent même s'ils ont 0 utilisateurs
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = [];

        foreach ($months as $month) {
            // Cherche si on a un résultat pour ce mois
            $found = false;
            foreach ($usersByMonth as $row) {
                if ($row['month'] === $month) {
                    $data[] = ['month' => $month, 'count' => (int) $row['count']];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = ['month' => $month, 'count' => 0];
            }
        }

        // Récupère la répartition par sexe pour le chart doughnut
        $sexeDistribution = $userRepository->countUserBySexe(null);

        // Récupère les stats par année de naissance
        $birthYearStats = $userRepository->countUsersByBirthYear();

        return $this->render('admin/user/stats.html.twig', [
            'usersByMonth' => $data,
            'sexeDistribution' => $sexeDistribution,
            'birthYearStats' => $birthYearStats,
        ]);
    }


}
