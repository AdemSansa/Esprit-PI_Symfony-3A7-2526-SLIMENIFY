<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number', 
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Please enter a valid phone number (exactly 8 digits).'
                    ])
                ]
            ])
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date of Birth',
                'constraints' => [
                    new Callback(function ($date, ExecutionContextInterface $context) {
                        if (!$date) return;
                        
                        $user = $context->getRoot()->getData();
                        // Apply the +20 years old check to all users modifying their profile
                        if ($user instanceof User) {
                            $twentyYearsAgo = new \DateTime('-20 years');
                            if ($date > $twentyYearsAgo) {
                                $context->buildViolation('You must be at least 20 years old.')
                                    ->addViolation();
                            }
                        }
                    }),
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'choices'  => [
                    'Male' => 'Male',
                    'Female' => 'Female',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'label' => 'Gender',
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(['message' => 'Please select your gender (Male or Female).'])
                ]
            ])
            ->add('photoUrl', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Profile Photo (Image file)', 
                'mapped' => false, 
                'required' => false
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
