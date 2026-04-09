<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'First Name'],
                'constraints' => [
                    new NotBlank(['message' => 'First name cannot be blank.']),
                    new Length(['min' => 2, 'max' => 50, 'minMessage' => 'First name must be at least {{ limit }} characters.']),
                ]
            ])
            ->add('lastName', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Last Name'],
                'constraints' => [
                    new Length(['min' => 2, 'max' => 50, 'minMessage' => 'Last name must be at least {{ limit }} characters.']),
                ]
            ])
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Email Address'],
                'constraints' => [
                    new NotBlank(['message' => 'Email cannot be blank.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Phone Number'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\+?[0-9]{8,15}$/',
                        'message' => 'Please enter a valid phone number.'
                    ])
                ]
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Patient' => 'patient',
                    'Therapist' => 'therapist',
                    'Admin' => 'admin',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Password (leave empty to keep current)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'New password'],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
