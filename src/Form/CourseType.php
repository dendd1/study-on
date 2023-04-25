<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Код курса',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Код курса не должно быть пустым']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Код курса не может содержать более {{ limit }} символов']),
                ],
                'attr' => [
                    'placeholder' => 'Укажите код курса',
                    'class' => 'form-control w-100 mb-2 fs-5'
                ]

            ])
            ->add('name', TextType::class, [
                'label' => 'Название',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Название курса не должно быть пустым']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Название  курса не может содержать более {{ limit }} символов']),
                ],
                'attr' => [
                    'placeholder' => 'Укажите название курса',
                    'class' => 'form-control w-100 mb-2 fs-5'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Описание  курса не может содержать более {{ limit }} символов']),
                ],
                'attr' => [
                    'placeholder' => 'Опишите ваш курс курса',
                    'class' => 'form-control w-100 mb-4 fs-5'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Сохранить',
                'attr' => [
                    'class' => 'btn btn-outline-primary fs-5 w-100',

                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
