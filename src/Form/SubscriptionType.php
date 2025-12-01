<?php

namespace App\Form;

use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\Plan;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Subscription|null $subscription */
        $subscription = $builder->getData();

        $startDateOptions = [
            'widget' => 'single_text',
            'required' => true,
            'attr' => [
                'class' => 'js-datepicker',
                'placeholder' => 'Select start date'
            ]
        ];
        
        // Only set current date if we're editing an existing subscription with a start date
        if ($subscription && $subscription->getStartDate()) {
            $startDateOptions['data'] = $subscription->getStartDate();
        }

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $subscription = $event->getData();
                $form = $event->getForm();
                
                // If no start date but we have an end date, copy end date to start date
                if (!$subscription->getStartDate() && $subscription->getEndDate()) {
                    $subscription->setStartDate($subscription->getEndDate());
                }
                
                // If still no start date, set it to now
                if (!$subscription->getStartDate()) {
                    $subscription->setStartDate(new \DateTime());
                }
                
                $event->setData($subscription);
            }
        );
        
        // Add validation for start date
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $subscription = $event->getData();
                $form = $event->getForm();
                
                if (null === $subscription->getStartDate()) {
                    $form->get('startDate')->addError(new FormError('Please select a start date'));
                }
            }
        );


        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'required' => false,
                'placeholder' => 'Select a user',
            ])
            ->add('plan', EntityType::class, [
                'class' => Plan::class,
                'choice_label' => 'name',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Canceled' => 'canceled',
                ],
            ])
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('autoRenew')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}
