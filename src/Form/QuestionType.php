<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('questionText', TextType::class, [
                'label' => 'Question Text',
                'attr' => ['class' => 'auth-input', 'placeholder' => 'Enter the question text here'],
            ])
            ->add('required', CheckboxType::class, [
                'label' => 'Is this question required?',
                'required' => false,
                'attr' => ['class' => 'auth-checkbox'],
                'label_attr' => ['class' => 'auth-checkbox-label'],
            ])
            ->add('imagePath', TextType::class, [
                'label' => 'Image Path/URL',
                'required' => false,
                'empty_data' => '',
                'attr' => ['class' => 'auth-input', 'placeholder' => 'https://example.com/image.jpg'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}
