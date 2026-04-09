<?php

namespace App\Form;

use App\Entity\Supplier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Supplier Name',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'Enter supplier name']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'supplier@example.com']
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '20123456']
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Physical Address',
                'required' => false,
                'attr' => ['class' => 'form-input', 'rows' => 3, 'placeholder' => 'Address details...']
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('country', TextType::class, [
                'label' => 'Country',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive'
                ],
                'attr' => ['class' => 'form-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Supplier::class,
        ]);
    }
}
