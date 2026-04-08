<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
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
                'label' => 'Event Description',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Format',
                'choices' => [
                    'Online' => 'online',
                    'In-Person' => 'physique',
                    'Hybrid' => 'hybride',
                ]
            ])
            ->add('dateStart', DateTimeType::class, [
                'label' => 'Start Date & Time',
                'html5' => true,
                'widget' => 'single_text'
            ])
            ->add('dateEnd', DateTimeType::class, [
                'label' => 'End Date & Time',
                'html5' => true,
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('location', TextType::class, [
                'label' => 'Location / Meeting Link',
                'required' => false,
                'attr' => ['class' => 'location-field']
            ])
            // 📍 Coordinate Fields (Hidden from UI, used by JS)
            ->add('latitude', HiddenType::class)
            ->add('longitude', HiddenType::class)
            
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Max Capacity',
                'required' => false,
                'attr' => ['min' => 1]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Event Cover Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP)',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
