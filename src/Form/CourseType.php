<?php

namespace App\Form;

use App\Entity\Course;
use App\Enum\PaymentStatus;
use App\Service\BillingClient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CourseType extends AbstractType
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entity = $builder->getData();
        if ($entity->getCode() != null) {
            $billingCourse = $this->billingClient->getCourse($entity->getCode());
        } else {
            $billingCourse = [];
        }
        if (!isset($billingCourse['price'])) {
            $billingCourse['price'] = 0;
        }
        if (!isset($billingCourse['type'])) {
            $billingCourse['type'] = 'free';
        }
        $builder
            ->add('code', TextType::class, [
                'label' => 'Код курса',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(['message' => 'Код курса не должен быть пустым']),
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
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(['message' => 'Название курса не должно быть пустым']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Название курса не может содержать более {{ limit }} символов']),
                ],
                'attr' => [
                    'placeholder' => 'Укажите название курса',
                    'class' => 'form-control w-100 mb-2 fs-5'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Описание  курса не может содержать более {{ limit }} символов']),
                ],
                'attr' => [
                    'placeholder' => 'Опишите ваш курс',
                    'class' => 'form-control w-100 mb-4 fs-5'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Тип',
                'choices' => [
                    'бесплатный' => 0,
                    'аренда' => 1,
                    'покупка' => 2,
                ],
                'attr' => [
                    'placeholder' => 'Опишите ваш курс',
                    'class' => 'form-control w-100 mb-4 fs-5'
                ],
                'required' => true,
                'mapped' => false,
                'data' => PaymentStatus::VALUES[$billingCourse['type']],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Цена',
                'attr' => [
                    'placeholder' => 'Опишите ваш курс курса',
                    'class' => 'form-control w-100 mb-4 fs-5',
                    'min' => 0
                ],
                'currency' => 'RUB',
                'html5' => true,
                'mapped' => false,
                'empty_data' => 0,
                'data' => $billingCourse['price'],
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
