<?php

namespace App\Form\Admin2;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Ім\'я'])
            ->add('surname', TextType::class, ['label' => 'Прізвище'])
            ->add('phone', TextType::class, ['label' => 'Телефон'])
            ->add('email', TextType::class, ['label' => 'Email'])
            ->add('address', TextareaType::class, [
                'label'    => 'Адреса',
                'required' => false,
            ])
            ->add('novaPoshtaCity', TextType::class, [
                'label'    => 'Місто (Нова Пошта)',
                'required' => false,
            ])
            ->add('novaPoshtaOffice', TextType::class, [
                'label'    => 'Відділення (Нова Пошта)',
                'required' => false,
            ])
            ->add('confirmed', SwitchType::class, [
                'label'     => 'Email підтверджено',
                'on_value'  => true,
                'off_value' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
