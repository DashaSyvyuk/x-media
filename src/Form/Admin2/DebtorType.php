<?php

namespace App\Form\Admin2;

use App\Entity\Currency;
use App\Entity\Debtor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DebtorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Ім\'я'])
            ->add('currency', EntityType::class, [
                'class'        => Currency::class,
                'label'        => 'Валюта',
                'choice_label' => fn(Currency $currency): string => sprintf('%s (%s)', $currency->getTitle(), $currency->getCode()),
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
            'data_class' => Debtor::class,
        ]);
    }
}
