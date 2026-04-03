<?php

namespace App\Form;

use App\Entity\Question;
use App\Entity\Quiz;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => ['class' => 'auth-input', 'placeholder' => 'Enter the quiz title'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (Optional)',
                'required' => false,
                'attr' => ['class' => 'auth-input', 'rows' => 4, 'placeholder' => 'A short summary...'],
            ])
            ->add('category', TextType::class, [
                'label' => 'Category (Optional)',
                'required' => false,
                'attr' => ['class' => 'auth-input', 'placeholder' => 'e.g., General Psychology'],
            ])

            ->add('active', CheckboxType::class, [
                'label' => 'Is this quiz currently active?',
                'required' => false,
                'attr' => ['class' => 'auth-checkbox'],
                'label_attr' => ['class' => 'auth-checkbox-label'],
            ])
            ->add('questions', EntityType::class, [
                'class' => Question::class,
                'choice_label' => 'questionText',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Select Questions',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
        ]);
    }
}
