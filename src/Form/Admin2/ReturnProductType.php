<?php

namespace App\Form\Admin2;

use App\Entity\ReturnProduct;
use App\Entity\Supplier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReturnProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label'   => 'Статус',
                'choices' => array_flip(ReturnProduct::STATUSES),
            ])
            ->add('name', TextType::class, ['label' => 'Ім\'я'])
            ->add('surname', TextType::class, [
                'label'    => 'Прізвище',
                'required' => false,
            ])
            ->add('phone', TextType::class, ['label' => 'Телефон'])
            ->add('email', TextType::class, [
                'label'    => 'Email',
                'required' => false,
            ])
            ->add('ttn', TextType::class, [
                'label'    => 'ТТН',
                'required' => false,
            ])
            ->add('supplier', EntityType::class, [
                'class'        => Supplier::class,
                'label'        => 'Постачальник',
                'choice_label' => 'title',
                'required'     => false,
            ])
            ->add('product', ProductEntityIdType::class, ['label' => 'ID товару'])
            ->add('amount', IntegerType::class, ['label' => 'Сума'])
            ->add('reason', TextareaType::class, [
                'label'    => 'Причина',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReturnProduct::class,
        ]);
    }
}
