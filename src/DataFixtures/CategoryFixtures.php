<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['nom' => 'Mathématiques', 'slug' => 'mathematiques', 'couleur' => '#3498db', 'icone' => '📐'],
            ['nom' => 'Statistiques', 'slug' => 'statistiques', 'couleur' => '#27ae60', 'icone' => '📊'],
            ['nom' => 'Algèbre', 'slug' => 'algebre', 'couleur' => '#e74c3c', 'icone' => '🔢'],
            ['nom' => 'Analyse', 'slug' => 'analyse', 'couleur' => '#9b59b6', 'icone' => '📈'],
            ['nom' => 'Géométrie', 'slug' => 'geometrie', 'couleur' => '#e67e22', 'icone' => '🌐'],
        ];

        foreach ($categories as $cat) {
            $category = new Category();
            $category->setNom($cat['nom']);
            $category->setSlug($cat['slug']);
            $category->setCouleur($cat['couleur']);
            $category->setIcone($cat['icone']);
            $manager->persist($category);
        }

        $manager->flush();
    }
}