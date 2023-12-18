<?php

namespace App\Form;

use App\Entity\ProductCharacteristic;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCharacteristicType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва', 'required' => true])
            ->add('value', TextType::class, ['label' => 'Значення', 'required' => true])
            ->add('position', null, ['label' => 'Пріоритет', 'required' => true])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductCharacteristic::class,
        ]);
    }
}
