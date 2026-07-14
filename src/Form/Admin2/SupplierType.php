<?php

namespace App\Form\Admin2;

use App\Entity\Currency;
use App\Entity\Supplier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('name', TextType::class, ['label' => 'Ім\'я', 'required' => false])
            ->add('surname', TextType::class, ['label' => 'Прізвище', 'required' => false])
            ->add('phone', TextType::class, ['label' => 'Телефон', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => false])
            ->add('address', TextareaType::class, ['label' => 'Адреса', 'required' => false, 'attr' => ['rows' => 2]])
            ->add('bankAccount', TextType::class, ['label' => 'Рахунок', 'required' => false])
            ->add('currency', EntityType::class, [
                'class'        => Currency::class,
                'label'        => 'Валюта',
                'choice_label' => 'title',
                'required'     => false,
            ])
            ->add('orderStorageDays', IntegerType::class, [
                'label'    => 'Термін зберігання замовлення (днів)',
                'required' => false,
                'attr'     => ['min' => 0],
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
            'data_class' => Supplier::class,
        ]);
    }
}
