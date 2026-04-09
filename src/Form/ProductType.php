<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Supplier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['class' => 'auth-input', 'placeholder' => 'Enter product name']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (Optional)',
                'required' => false,
                'attr' => ['class' => 'auth-input', 'rows' => 4, 'placeholder' => 'Details...']
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2,
                'attr' => ['class' => 'auth-input', 'placeholder' => '0.00']
            ])
            ->add('stockQuantity', IntegerType::class, [
                'label' => 'Stock Quantity',
                'attr' => ['class' => 'auth-input', 'placeholder' => '0']
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'required' => false,
                'placeholder' => 'Select category',
                'choices' => [
                    'Authorized Vitamins' => 'Authorized Vitamins',
                    'Psychology Books' => 'Psychology Books',
                    'Relaxing Products' => 'Relaxing Products',
                    'Therapeutic Games & Activities' => 'Therapeutic Games & Activities',
                ],
                'attr' => ['class' => 'auth-input']
            ])
            ->add('expirationDate', DateType::class, [
                'label' => 'Expiration Date',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'auth-input']
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a Supplier',
                'attr' => ['class' => 'auth-input']
            ])
            ->add('photoUrl', UrlType::class, [
                'label' => 'Photo URL',
                'required' => false,
                'attr' => ['class' => 'auth-input', 'placeholder' => 'https://...']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Available' => 'available',
                    'Out of Stock' => 'out_of_stock',
                    'Discontinued' => 'discontinued'
                ],
                'attr' => ['class' => 'auth-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
