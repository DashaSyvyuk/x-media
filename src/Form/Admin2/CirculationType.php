<?php

namespace App\Form\Admin2;

use App\Entity\AdminUser;
use App\Entity\Circulation;
use App\Entity\Currency;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CirculationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adminUser', EntityType::class, [
                'class'        => AdminUser::class,
                'label'        => 'Адмін',
                'placeholder'  => 'Оберіть адміна',
                'choice_label' => 'email',
                'required'     => true,
            ])
            ->add('currency', EntityType::class, [
                'class'        => Currency::class,
                'label'        => 'Валюта',
                'choice_label' => fn (Currency $currency): string => sprintf(
                    '%s (%s)',
                    $currency->getTitle(),
                    $currency->getCode(),
                ),
                'required'     => true,
            ])
            ->add('active', SwitchType::class, [
                'label'     => 'Активний',
                'on_value'  => true,
                'off_value' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Circulation::class,
        ]);
    }
}
