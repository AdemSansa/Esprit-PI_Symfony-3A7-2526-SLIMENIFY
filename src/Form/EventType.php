<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Event Title'
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4]
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Online' => 'online',
                    'In-Person (Physique)' => 'physique',
                    'Hybrid' => 'hybride',
                ]
            ])
            ->add('dateStart', DateTimeType::class, [
                'html5' => true,
                'widget' => 'single_text'
            ])
            ->add('dateEnd', DateTimeType::class, [
                'html5' => true,
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'location-field']
            ])
            ->add('maxParticipants', IntegerType::class, [
                'required' => false,
                'attr' => ['min' => 1]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Draft' => 'draft',
                    'Published' => 'published',
                    'Cancelled' => 'cancelled',
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Event Image (JPG/PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG or PNG image',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
