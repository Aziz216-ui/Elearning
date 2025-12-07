<?php

namespace App\Service;

use App\Repository\CoursRepository;
use App\Repository\AuteurRepository;

class GlobalChatbot
{
    public function __construct(
        private readonly CoursRepository $coursRepository,
        private readonly AuteurRepository $auteurRepository,
    ) {}

    public function answer(string $question): string
    {
        $q = mb_strtolower(trim($question));

        if ($q === '' || mb_strlen($q) < 3) {
            return "Bonjour, je peux vous aider à comprendre les cours, les auteurs et les prix sur la plateforme. Posez-moi une question plus précise.";
        }

        $allCourses = $this->coursRepository->findAll();
        $totalCourses = count($allCourses);

        $allAuthors = $this->auteurRepository->findAll();
        $totalAuthors = count($allAuthors);

        // Prix min / max / moyen
        $statsPrice = $this->coursRepository->getMinMaxPrice(null);
        $minPrice = $statsPrice['minPrice'] ?? null;
        $maxPrice = $statsPrice['maxPrice'] ?? null;
        $avgPrice = null;
        if ($totalCourses > 0) {
            $sum = 0.0;
            foreach ($allCourses as $c) {
                $sum += (float) $c->getPrice();
            }
            $avgPrice = $sum / $totalCourses;
        }

        // Catégories
        $byCategory = $this->coursRepository->countByCategory();

        // Auteurs
        $topAuthors = $this->auteurRepository->topAuthors(3);
        $authorsWithoutCourses = $this->auteurRepository->authorsWithoutCourses();

        // Questions sur le nombre de cours
        if (str_contains($q, 'combien de cours') || str_contains($q, 'nombre de cours') || str_contains($q, 'tous les cours')) {
            if ($totalCourses === 0) {
                return "Il n'y a actuellement aucun cours enregistré sur la plateforme.";
            }

            return sprintf("Il y a actuellement %d cours disponibles sur la plateforme.", $totalCourses);
        }

        // Questions sur les prix en général
        if (str_contains($q, 'prix') || str_contains($q, 'tarif') || str_contains($q, 'coût') || str_contains($q, 'cout')) {
            if ($totalCourses === 0 || $minPrice === null || $maxPrice === null) {
                return "Je n'ai pas encore assez d'informations pour indiquer les prix des cours.";
            }

            $parts = [];
            $parts[] = sprintf("Les prix des cours vont de %.2f € à %.2f €.", (float) $minPrice, (float) $maxPrice);
            if ($avgPrice !== null) {
                $parts[] = sprintf("Le prix moyen d'un cours est d'environ %.2f €.", $avgPrice);
            }

            return implode(' ', $parts);
        }

        // Questions sur les catégories
        if (str_contains($q, 'catégorie') || str_contains($q, 'categorie') || str_contains($q, 'types de cours') || str_contains($q, 'type de cours')) {
            if (!$byCategory) {
                return "Aucune catégorie n'est encore enregistrée pour les cours.";
            }

            $labels = [];
            foreach ($byCategory as $row) {
                $cat = $row['category'] ?? 'Non classé';
                $labels[] = sprintf("%s (%d cours)", $cat, (int) $row['total']);
            }

            return 'Les principales catégories de cours sont : ' . implode(', ', $labels) . '.';
        }

        // Questions sur les auteurs en général
        if (str_contains($q, 'auteur') || str_contains($q, 'formateur') || str_contains($q, 'prof')) {
            // Nombre total d'auteurs
            if (str_contains($q, 'combien') || str_contains($q, 'nombre')) {
                if ($totalAuthors === 0) {
                    return "Il n'y a actuellement aucun auteur enregistré sur la plateforme.";
                }

                return sprintf("Il y a %d auteur(s) enregistré(s) sur la plateforme.", $totalAuthors);
            }

            // Auteurs avec beaucoup de cours
            if (str_contains($q, 'beaucoup de cours') || str_contains($q, 'plus de cours') || str_contains($q, 'plusieurs cours')) {
                if (!$topAuthors) {
                    return "Je ne trouve pas encore d'auteurs avec plusieurs cours.";
                }

                $parts = [];
                foreach ($topAuthors as $row) {
                    $a = $row['auteur'];
                    $nb = (int) $row['nbCours'];
                    $name = trim(($a->getPrenom() ?? '') . ' ' . ($a->getNom() ?? ''));
                    $parts[] = sprintf("%s (%d cours)", $name !== '' ? $name : 'Auteur sans nom', $nb);
                }

                return 'Les auteurs avec le plus de cours sont : ' . implode(', ', $parts) . '.';
            }

            // Auteurs sans cours
            if (str_contains($q, 'sans cours') || str_contains($q, 'aucun cours')) {
                if (!$authorsWithoutCourses) {
                    return "Tous les auteurs enregistrés ont au moins un cours.";
                }

                $names = [];
                foreach ($authorsWithoutCourses as $a) {
                    $name = trim(($a->getPrenom() ?? '') . ' ' . ($a->getNom() ?? ''));
                    $names[] = $name !== '' ? $name : 'Auteur sans nom';
                }

                return 'Les auteurs qui n\'ont pas encore de cours sont : ' . implode(', ', $names) . '.';
            }

            // Réponse par défaut sur les auteurs
            if (!$topAuthors) {
                return "Il n'y a pas encore d'auteurs enregistrés avec des cours.";
            }

            $parts = [];
            foreach ($topAuthors as $row) {
                $a = $row['auteur'];
                $nb = (int) $row['nbCours'];
                $name = trim(($a->getPrenom() ?? '') . ' ' . ($a->getNom() ?? ''));
                $parts[] = sprintf("%s (%d cours)", $name !== '' ? $name : 'Auteur sans nom', $nb);
            }

            return 'Quelques auteurs actifs sur la plateforme : ' . implode(', ', $parts) . '.';
        }

        // Questions sur la plateforme / inscription globale
        if (str_contains($q, 'inscription') || str_contains($q, 'inscrire') || str_contains($q, 'plateforme') || str_contains($q, 'elearning')) {
            return "Pour utiliser la plateforme, vous devez créer un compte, parcourir les cours disponibles, ajouter ceux qui vous intéressent à votre panier puis valider votre inscription. Une fois inscrit, vos cours apparaîtront dans votre espace personnel.";
        }

        // Question sur les cours populaires ou chers/moins chers
        if (str_contains($q, 'plus cher') || str_contains($q, 'moins cher') || str_contains($q, 'cher') || str_contains($q, 'pas cher')) {
            $mostExpensive = $this->coursRepository->findMostExpensive(3);
            $cheapest = $this->coursRepository->findCheapest(3);

            $lines = [];
            if ($mostExpensive) {
                $tmp = [];
                foreach ($mostExpensive as $c) {
                    $tmp[] = sprintf("%s (%.2f €)", $c->getTitle(), $c->getPrice());
                }
                $lines[] = 'Parmi les cours les plus chers : ' . implode(', ', $tmp) . '.';
            }
            if ($cheapest) {
                $tmp = [];
                foreach ($cheapest as $c) {
                    $tmp[] = sprintf("%s (%.2f €)", $c->getTitle(), $c->getPrice());
                }
                $lines[] = 'Parmi les cours les plus abordables : ' . implode(', ', $tmp) . '.';
            }

            if ($lines) {
                return implode(' ', $lines);
            }

            return "Je n'ai pas encore assez d'informations pour comparer les prix des cours.";
        }

        // Cours publiés / non publiés
        if (str_contains($q, 'publié') || str_contains($q, 'publie') || str_contains($q, 'en ligne')) {
            $published = $this->coursRepository->findPublished(true);
            $unpublished = $this->coursRepository->findPublished(false);

            $nbPub = count($published);
            $nbUnpub = count($unpublished);

            if ($nbPub === 0 && $nbUnpub === 0) {
                return "Il n'y a aucun cours enregistré pour le moment.";
            }

            if ($nbUnpub === 0) {
                return sprintf("Tous les %d cours enregistrés sont publiés.", $nbPub);
            }

            if ($nbPub === 0) {
                return sprintf("Aucun cours n'est encore publié. %d cours sont enregistrés en brouillon.", $nbUnpub);
            }

            return sprintf("Il y a %d cours publiés et %d cours non publiés sur la plateforme.", $nbPub, $nbUnpub);
        }

        // Cours gratuits ou très peu chers
        if (str_contains($q, 'gratuit') || str_contains($q, '0€') || str_contains($q, '0 €') || str_contains($q, 'moins de')) {
            $freeOrCheap = [];
            $threshold = 10.0; // cours "peu chers" < 10 €
            foreach ($allCourses as $c) {
                $price = (float) $c->getPrice();
                if ($price <= 0.0 || $price < $threshold) {
                    $freeOrCheap[] = $c;
                }
            }

            if (!$freeOrCheap) {
                return "Je ne trouve pas de cours gratuits ou très peu chers pour le moment.";
            }

            $labels = [];
            foreach ($freeOrCheap as $c) {
                $labels[] = sprintf("%s (%.2f €)", $c->getTitle(), $c->getPrice());
            }

            return 'Voici quelques cours gratuits ou peu chers : ' . implode(', ', $labels) . '.';
        }

        // Catégories les plus populaires
        if (str_contains($q, 'catégorie la plus populaire') || str_contains($q, 'categorie la plus populaire') || str_contains($q, 'catégories les plus populaires') || str_contains($q, 'categories les plus populaires')) {
            if (!$byCategory) {
                return "Je ne trouve pas encore de catégories de cours enregistrées.";
            }

            // countByCategory est déjà classé par total DESC
            $top = array_slice($byCategory, 0, 3);
            $labels = [];
            foreach ($top as $row) {
                $cat = $row['category'] ?? 'Non classé';
                $labels[] = sprintf("%s (%d cours)", $cat, (int) $row['total']);
            }

            if (count($labels) === 1) {
                return 'La catégorie la plus populaire est : ' . $labels[0] . '.';
            }

            return 'Les catégories les plus populaires sont : ' . implode(', ', $labels) . '.';
        }

        // Réponse générique avec quelques statistiques synthétiques
        $summary = [];
        if ($totalCourses > 0) {
            $summary[] = sprintf("Il y a %d cours au total sur la plateforme.", $totalCourses);
        }
        if ($minPrice !== null && $maxPrice !== null) {
            $summary[] = sprintf("Les prix vont de %.2f € à %.2f €.", (float) $minPrice, (float) $maxPrice);
        }
        if ($byCategory) {
            $summary[] = sprintf("Il y a %d catégories de cours différentes.", count($byCategory));
        }

        if ($summary) {
            return "Je ne suis pas sûr de bien comprendre votre question. " . implode(' ', $summary);
        }

        return "Je ne trouve pas encore d'information pour répondre à cette question. Essayez de poser une question sur le nombre de cours, les prix, les catégories ou les auteurs.";
    }
}
