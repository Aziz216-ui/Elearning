<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationFormType extends AbstractType
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // read NotNull message from entity property metadata if present
        $invalidMessage = null;


        $builder
            ->add('email')
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([ 'message' => 'Vous devez accepter les conditions.']),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // Map to the entity's transient property so entity constraints apply
                'mapped' => true,
                'required' => true,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('name')
            ->add('lastname')
            ->add('birthdate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'model_timezone' => 'UTC',
                'view_timezone' => 'UTC',
                'label' => 'Date de naissance',
                'required' => false,
                'attr' => [
                    'placeholder' => 'AAAA-MM-JJ'
                ],

            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Homme' => 'M',
                    'Femme' => 'F',
                ],
                'placeholder' => 'Choisir',
                'required' => true,
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,

        ]);
    }
}