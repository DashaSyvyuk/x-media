<?php

namespace App\Form\Admin2;

use App\Entity\Promotion;
use App\Entity\PromotionProduct;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Admin2PromotionProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('promotion', EntityType::class, [
            'class'       => Promotion::class,
            'label'       => 'Акція',
            'placeholder' => 'Оберіть акцію',
            'required'    => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromotionProduct::class,
        ]);
    }
}
