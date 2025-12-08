<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\ForumPost;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;


class ForumPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre'
            ])

            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu'
            ])

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function (Category $category) {
                    $icon = $category->getIcone() ?? '📁';
                    return $icon . ' ' . $category->getNom();
                },
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => false
            ])

            ->add('image', FileType::class, [
    'label' => 'Image (JPG, PNG, GIF)',
    'mapped' => false, // le champ n'est pas directement lié à l'Entity
    'required' => false,
    'constraints' => [
        new File([
            'maxSize' => '5M',
            'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
            'mimeTypesMessage' => 'Formats non autorisés ',
            'maxSizeMessage' => "L'image ne doit pas dépasser 5 Mo."
        ])
    ]
])



            ->add('enabled', CheckboxType::class, [
                'label' => 'Activer le post',
                'required' => false,
                'data' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumPost::class,
        ]);
    }
}
