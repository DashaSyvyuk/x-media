<?php

namespace App\Form\Admin2;

use App\Entity\VendorOrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VendorOrderItemEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextareaType::class, [
                'label' => 'Товар',
                'attr'  => ['rows' => 2],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Кіл-сть',
                'attr'  => ['min' => 1],
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Ціна',
                'attr'  => ['min' => 0],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VendorOrderItem::class,
        ]);
    }
}
