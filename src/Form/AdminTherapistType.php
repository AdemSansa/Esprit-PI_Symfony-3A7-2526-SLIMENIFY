<?php

namespace App\Form;

use App\Entity\Therapist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;

class AdminTherapistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', null, [
                'constraints' => [
                    new NotBlank(['message' => 'First name cannot be blank.']),
                    new Length(['min' => 2, 'max' => 50]),
                ]
            ])
            ->add('lastName', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Last name cannot be blank.']),
                    new Length(['min' => 2, 'max' => 50]),
                ]
            ])
            ->add('email', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Email cannot be blank.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                ]
            ])
            ->add('phoneNumber', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Phone number cannot be blank.']),
                    new Regex([
                        'pattern' => '/^\+?[0-9]{8,15}$/',
                        'message' => 'Please enter a valid phone number (8 to 15 digits).'
                    ])
                ]
            ])
            ->add('specialization', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                 'choices'  => [
                    'Psychologie' => 'Psychologie',
                    'Sexologie' => 'Sexologie',
                    'Thérapie de couple' => 'Thérapie de couple',
                    'Psychiatrie' => 'Psychiatrie',
                ],
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'constraints' => [
                    new Length(['min' => 20, 'minMessage' => 'The biography should be at least 20 characters long.'])
                ]
            ])
            ->add('consultationType', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices'  => [
                    'Online' => 'ONLINE',
                    'In Person' => 'IN_PERSON',
                    'Both' => 'BOTH',
                ],
            ])
            ->add('status', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices'  => [
                    'Active' => 'ACTIVE',
                    'Inactive' => 'INACTIVE',
                ],
            ])
            ->add('password', null, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ]
            ])
            ->add('photoUrl')
            ->add('diplomaPath')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Therapist::class,
        ]);
    }
}
