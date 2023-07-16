<?php

namespace App\Form;

use App\Entity\ProductQueue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductQueueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product')
            ->add('supplier')
            ->add('price')
            ->add('quantity')
            ->add('active')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductQueue::class,
        ]);
    }
}
