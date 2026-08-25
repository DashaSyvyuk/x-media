<?php

namespace App\Form\Admin2;

use App\Entity\Supplier;
use App\Entity\Warranty;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class WarrantyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label'   => 'Статус',
                'choices' => array_flip(Warranty::STATUSES),
            ])
            ->add('name', TextType::class, [
                'label'      => 'Ім\'я',
                'required'   => false,
                'empty_data' => '',
            ])
            ->add('surname', TextType::class, [
                'label'    => 'Прізвище',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label'      => 'Телефон',
                'required'   => false,
                'empty_data' => '',
            ])
            ->add('email', TextType::class, [
                'label'    => 'Email',
                'required' => false,
            ])
            ->add('fromClientTtn', TextType::class, [
                'label'    => 'ТТН від клієнта',
                'required' => false,
            ])
            ->add('toClientTtn', TextType::class, [
                'label'    => 'ТТН до клієнта',
                'required' => false,
            ])
            ->add('supplierOrderNumber', TextType::class, [
                'label'    => 'Номер замовлення постачальника',
                'required' => false,
            ])
            ->add('orderNumber', TextType::class, [
                'label'    => 'Номер замовлення',
                'required' => false,
            ])
            ->add('supplier', EntityType::class, [
                'class'        => Supplier::class,
                'label'        => 'Постачальник',
                'choice_label' => 'title',
                'required'     => false,
                'placeholder'  => '—',
            ])
            ->add('product', ProductEntityIdType::class, [
                'label'       => 'ID товару',
                'required'    => true,
                'constraints' => [
                    new NotNull(message: 'Вкажіть ID товару.'),
                ],
            ])
            ->add('expenses', IntegerType::class, [
                'label'      => 'Витрати (грн)',
                'required'   => false,
                'empty_data' => 0,
            ])
            ->add('reason', TextareaType::class, [
                'label'    => 'Причина',
                'required' => false,
                'attr'     => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Warranty::class,
        ]);
    }
}
