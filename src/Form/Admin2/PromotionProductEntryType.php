<?php

namespace App\Form\Admin2;

use App\Entity\PromotionProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionProductEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('product', ProductEntityIdType::class, [
            'help' => 'Числовий ID зі списку товарів admin2',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromotionProduct::class,
        ]);
    }
}
