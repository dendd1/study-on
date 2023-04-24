<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Название урока',
                'required' => true,
                'empty_data' => '',
            ])
            ->add('content', TextType::class, [
                'label' => 'Контент',
                'required' => true,
                'empty_data' => '',
            ])
            ->add('number', NumberType::class, [
                'label' => 'Номер урока',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'max' => 10000,
                    'min' => 1,
                ]
            ])
            ->add('course', HiddenType::class)
        ;
        $builder->get('course')
            ->addModelTransformer(
                new CallbackTransformer(
                    function ($courseAsObj): string {
                        return $courseAsObj->getId();
                    },
                    function ($courseId): Course {
                        return $this->entityManager
                            ->getRepository(Course::class)
                            ->find($courseId);
                    }
                )
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
