<?php

namespace App\Form;

use App\Entity\PromotionProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPromotionType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('promotion');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromotionProduct::class,
        ]);
    }
}
