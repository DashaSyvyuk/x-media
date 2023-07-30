<?php

namespace App\Form;

use App\Entity\SupplierOrderProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierOrderProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product')
            ->add('supplierProductPrice', null, [
                'disabled' => true,
                'label' => 'Ціна закупки'
            ])
            ->add('price', null, [
                'label' => 'Ціна, за якою замовили'
            ])
            ->add('productPrice', null, [
                'disabled' => true,
                'label' => 'Ціна продажу'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierOrderProduct::class,
        ]);
    }
}
