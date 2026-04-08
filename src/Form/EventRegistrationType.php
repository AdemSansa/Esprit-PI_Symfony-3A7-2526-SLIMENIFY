<?php

namespace App\Form;

use App\Entity\Registration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('participantName', TextType::class, [
                'label' => 'Full Name'
            ])
            ->add('participantEmail', EmailType::class, [
                'label' => 'Email Address'
            ])
            ->add('participantPhone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false
            ])
            ->add('participantNotes', TextareaType::class, [
                'label' => 'Additional Notes',
                'required' => false,
                'attr' => ['rows' => 3]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Registration::class,
        ]);
    }
}
