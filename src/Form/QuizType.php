<?php

namespace App\Form;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Enum\PsychologyCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'label' => 'Description',
                'required' => true,
                'attr' => ['class' => 'auth-input', 'rows' => 4, 'placeholder' => 'A short summary...'],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'required' => true,
                'placeholder' => 'Select a category',
                'choices' => PsychologyCategory::choices(),
                'attr' => ['class' => 'auth-input'],
            ])

            ->add('questions', EntityType::class, [
                'class' => Question::class,
                'choice_label' => 'questionText',
                'choice_attr' => static function (?Question $question): array {
                    $category = $question?->getCategory() ?? '';
                    return [
                        'data-question-category' => mb_strtolower(trim($category)),
                        'data-question-category-label' => $category,
                    ];
                },
                'multiple' => true,
                'expanded' => true,
                'label' => 'Select Questions',
                'required' => true,
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
