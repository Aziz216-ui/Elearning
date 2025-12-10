<?php

namespace App\Form;

use App\Entity\Quiz;
use App\Form\QuestionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Cours;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du quiz',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ])
            ->add('totalPoints', IntegerType::class, [
                'label' => 'Points totaux',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'title',
                'placeholder' => 'Sélectionnez un cours',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('questions', CollectionType::class, [
                'entry_type' => QuestionType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Questions',
                'attr' => ['class' => 'questions-collection']
            ])
            ->add('is_visible', CheckboxType::class, [
                'label' => 'Rendre visible',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                    'data-toggle' => 'toggle',
                    'data-on' => 'Oui',
                    'data-off' => 'Non',
                    'data-onstyle' => 'success',
                    'data-offstyle' => 'secondary'
                ],
                'label_attr' => ['class' => 'form-check-label']
            ])
            ->add('isPublished', null, [
                'label' => 'Publier le quiz',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                    'data-toggle' => 'toggle',
                    'data-on' => 'Oui',
                    'data-off' => 'Non',
                    'data-onstyle' => 'success',
                    'data-offstyle' => 'secondary'
                ],
                'label_attr' => ['class' => 'form-check-label']
            ])
            ->add('timeLimit', IntegerType::class, [
                'label' => 'Limite de temps (en minutes)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1',
                    'max' => '120',
                    'required' => 'required'
                ],
                'help' => 'Durée maximale du quiz en minutes (1-120)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
        ]);
    }
}