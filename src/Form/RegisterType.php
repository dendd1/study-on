<?php

namespace App\Form;

use App\Security\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Почта',
                'attr' => ['class' => 'mb-3 w-100'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'mapped' => false,
                'options' => ['attr' => ['class' => 'mb-3 w-100']],
                'attr' => ['autocomplete' => 'new-password'],
                'invalid_message' => 'Пароли должны совпадать',
                'first_options'  => ['label' => 'Пароль'],
                'second_options' => ['label' => 'Подтвердите пароль'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Пароль обязателен к заполнению',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен содержать минимум 6 символов',
                        'max' => 255,
                        'maxMessage' => 'Пароль должен содержать максимум 255 символов',
                    ]),
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}