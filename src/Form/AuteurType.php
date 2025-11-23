<?php

namespace App\Form;

use App\Entity\Auteur;
use App\Entity\Cours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Count;

class AuteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('email', EmailType::class)
            // Champs facultatifs ajoutés
            ->add('specialite', TextType::class, [
                'required' => false,
                'label' => 'Spécialité / domaine',
            ])
            ->add('bio', TextareaType::class, [
                'required' => false,
                'label' => 'Bio courte',
                'attr' => ['rows' => 3],
            ])
            ->add('photoFile', FileType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Photo (JPG/PNG)',
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WEBP).',
                    ])
                ],
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'title',
                'multiple' => true,    
                'expanded' => false,     // true pour checkboxes, false pour select multiple
                'required' => true,      // rendre le champ obligatoire
                'by_reference' => false, // pour appeler addCour/removeCour
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'Veuillez sélectionner au moins un cours',
                    ])
                ],
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Auteur::class,
        ]);
    }
}
