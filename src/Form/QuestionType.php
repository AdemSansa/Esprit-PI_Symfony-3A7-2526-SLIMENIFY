<?php

namespace App\Form;

use App\Entity\Question;
use App\Enum\PsychologyCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'required' => true,
                'placeholder' => 'Select a category',
                'choices' => PsychologyCategory::choices(),
                'attr' => ['class' => 'auth-input'],
            ])
            ->add('required', CheckboxType::class, [
                'label' => 'Is this question required?',
                'required' => false,
                'attr' => ['class' => 'auth-checkbox'],
                'label_attr' => ['class' => 'auth-checkbox-label'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Question Image',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'auth-input', 'accept' => 'image/*'],
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
