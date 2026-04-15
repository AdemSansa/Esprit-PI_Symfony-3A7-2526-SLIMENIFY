<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Email cannot be blank']),
                    new Email(['message' => 'Please enter a valid email address.']),
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'You should agree to our terms.',
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter a password',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
            ])
            ->add('firstName', null, [
                'constraints' => [
                    new NotBlank(['message' => 'First name cannot be blank']),
                    new Length(['min' => 2, 'max' => 50, 'minMessage' => 'First name must be at least {{ limit }} characters']),
                ]
            ])
            ->add('lastName', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Last name cannot be blank']),
                    new Length(['min' => 2, 'max' => 50, 'minMessage' => 'Last name must be at least {{ limit }} characters']),
                ]
            ])
            ->add('phone', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Phone number cannot be blank']),
                    new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Please enter a valid phone number (exactly 8 digits).'
                    ])
                ]
            ])
            ->add('dateNaissance')
            ->add('gender', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices'  => [
                    'Male' => 'Male',
                    'Female' => 'Female',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('role', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices'  => [
                    'Patient' => 'patient',
                    'Therapist' => 'therapist',
                ],
                'expanded' => false, // Combobox
                'multiple' => false,
                'mapped' => false, // we will set this in the controller
                'data' => 'patient',
            ])
            ->add('specialization', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'Psychologie' => 'Psychologie',
                    'Sexologie' => 'Sexologie',
                    'Thérapie de couple' => 'Thérapie de couple',
                    'Psychiatrie' => 'Psychiatrie',
                ],
                'placeholder' => 'Choose a specialization...',
                'mapped' => false,
                'required' => false,
            ])
            ->add('consultationType', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices'  => [
                    'Online' => 'ONLINE',
                    'In Person' => 'IN_PERSON',
                    'Both' => 'BOTH',
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('diplomaPath', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Diploma (PDF/Image)',
            ])
            ->add('photoUrl', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Profile Photo',
            ])
            ->add('isVerified')
            
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
