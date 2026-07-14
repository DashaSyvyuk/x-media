<?php

namespace App\Form\Admin2;

use App\Entity\AdminUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordOptions = [
            'label'    => 'Пароль',
            'mapped'   => false,
            'required' => ! $options['is_edit'],
        ];

        if (! $options['is_edit']) {
            $passwordOptions['constraints'] = [new NotBlank()];
        }

        $builder
            ->add('email', TextType::class, ['label' => 'Логін (email)'])
            ->add('plainPassword', PasswordType::class, $passwordOptions)
            ->add('name', TextType::class, ['label' => 'Ім\'я'])
            ->add('surname', TextType::class, ['label' => 'Прізвище'])
            ->add('phone', TextType::class, ['label' => 'Телефон'])
            ->add('roles', ChoiceType::class, [
                'label'       => 'Роль',
                'choices'     => array_flip(AdminUser::ROLES),
                'multiple'    => true,
                'expanded'    => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdminUser::class,
            'is_edit'    => false,
        ]);
    }
}
