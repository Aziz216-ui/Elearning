<?php

namespace App\Form;

use App\Entity\Auteur;
use App\Entity\Cours;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Types de formulaire Symfony
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {$builder
        ->add('title', TextType::class, [
            'label' => 'Titre du cours',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Saisir le titre du cours'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer un titre']),
            ],
        ])
        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Saisir la description du cours'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer une description']),
            ],
        ])
        ->add('duration', DateTimeType::class, [
            'widget' => 'single_text',
            'html5' => true,
            'label' => 'Date et heure du cours',
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer la date et l’heure du cours',
                ]),
            ],
            'empty_data' => null,
            'input' => 'datetime_immutable',
        ])
        ->add('price', MoneyType::class, [
            'label' => 'Prix du cours',
            'currency' => 'TND',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Ex : 150'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer un prix']),
                new Positive(['message' => 'Le prix doit être positif']),
            ],
        ])
        ->add('category', TextType::class, [
            'label' => 'Catégorie',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Ex : Développement, Design, ...'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer une catégorie']),
            ],
        ])
        ->add('isPublished', CheckboxType::class, [
            'label' => 'Publié',
            'required' => false, // permet de laisser décoché
        ])
        ->add('auteur', EntityType::class, [
            'class' => Auteur::class,
            'choice_label' => 'nom', // ou 'prenom'
            'label' => 'Auteur du cours',
            'placeholder' => 'Sélectionner un auteur',
            'attr' => [
                'class' => 'form-select',
            ],
            'required' => true,
            'constraints' => [
                new NotBlank(['message' => 'Veuillez sélectionner un auteur']),
            ],
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}
