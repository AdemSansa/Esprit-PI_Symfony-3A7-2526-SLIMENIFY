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
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'e.g. Vitamin C 500mg']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-input', 'rows' => 3, 'placeholder' => 'Product details...']
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price (TND)',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '0.00']
            ])
            ->add('stockQuantity', IntegerType::class, [
                'label' => 'Stock Quantity',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '0']
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'required' => false,
                'placeholder' => 'Choose a category',
                'choices' => [
                    'Authorized Vitamins' => 'Authorized Vitamins',
                    'Psychology Books' => 'Psychology Books',
                    'Relaxing Products' => 'Relaxing Products',
                    'Therapeutic Games & Activities' => 'Therapeutic Games & Activities',
                ],
                'attr' => ['class' => 'form-input']
            ])
            ->add('expirationDate', DateType::class, [
                'label' => 'Expiration Date',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-input']
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'label' => 'Supplier',
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a supplier',
                'attr' => ['class' => 'form-input']
            ])
            ->add('photoUrl', UrlType::class, [
                'label' => 'Photo URL',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'Link to image...']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Available' => 'available',
                    'Out of Stock' => 'out_of_stock',
                    'Discontinued' => 'discontinued'
                ],
                'attr' => ['class' => 'form-input']
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
