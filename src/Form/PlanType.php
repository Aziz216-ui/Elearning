<?php

namespace App\Form;

use App\Entity\Plan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price', MoneyType::class, [
                'currency' => 'DT',
                'divisor' => 1,
            ])
            ->add('duration', ChoiceType::class, [
                'choices' => [
                    'Monthly' => 'monthly',
                    'Yearly' => 'yearly',
                    'Lifetime' => 'lifetime',
                ],
            ])
            ->add('isActive')
            ->add('maxCourses')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plan::class,
        ]);
    }
}
