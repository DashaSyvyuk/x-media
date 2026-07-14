<?php

namespace App\Form\Admin2;

use App\Entity\AdminUser;
use App\Entity\Warehouse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WarehouseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('city', TextType::class, ['label' => 'Місто'])
            ->add('address', TextareaType::class, ['label' => 'Адреса', 'required' => false, 'attr' => ['rows' => 2]])
            ->add('adminUser', EntityType::class, [
                'class'        => AdminUser::class,
                'label'        => 'Адмін',
                'choice_label' => 'email',
                'required'     => false,
            ])
            ->add('notes', TextareaType::class, ['label' => 'Нотатки', 'required' => false, 'attr' => ['rows' => 3]])
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
            'data_class' => Warehouse::class,
        ]);
    }
}
