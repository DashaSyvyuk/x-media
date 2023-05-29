<?php

namespace App\Form;

use App\Entity\ProductFilterAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilterAttributeType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filter', null, ['label' => 'Фільтр', 'required' => true])
            ->add('filterAttribute', null, ['label' => 'Параметр', 'required' => true])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductFilterAttribute::class,
        ]);
    }
}
