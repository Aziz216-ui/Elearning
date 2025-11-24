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
            ['nom' => 'Développement Web avec Symfony', 'slug' => 'Développemrnt Web avec Symfony', 'couleur' => '#3498db', 'icone' => '📐'],
            ['nom' => 'HTML & CSS', 'slug' => 'Html&CSS', 'couleur' => '#27ae60', 'icone' => '📊'],
            ['nom' => 'Introduction à PHP', 'slug' => 'Introduction a PHP', 'couleur' => '#e74c3c', 'icone' => '🔢'],
            ['nom' => 'JavaScript', 'slug' => 'JavaScript', 'couleur' => '#9b59b6', 'icone' => '📈'],
            ['nom' => 'MySQL', 'slug' => 'MySQL', 'couleur' => '#e67e22', 'icone' => '🌐'],
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