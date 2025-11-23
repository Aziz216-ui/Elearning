<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\ForumPost;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForumPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du post',
                'attr' => [
                    'placeholder' => 'Entrez le titre de votre post...',
                    'class' => 'form-control',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le titre est requis'
                    ]),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'placeholder' => 'Écrivez le contenu de votre post...',
                    'class' => 'form-control',
                    'rows' => 10
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le contenu est requis'
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function(Category $category) {
                    $icon = $category->getIcone() ?? '📁';
                    return $icon . ' ' . $category->getNom();
                },
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Choisissez la catégorie qui correspond le mieux à votre post'
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPG, PNG, GIF)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/gif'
                ],
                'help' => 'Taille maximum: 5 Mo',
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG ou GIF)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser {{ limit }} {{ suffix }}'
                    ])
                ]
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Activer le post',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Décochez pour mettre le post en brouillon',
                'data' => true // Par défaut activé
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumPost::class,
        ]);
    }
}