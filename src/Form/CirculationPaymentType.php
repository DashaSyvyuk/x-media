<?php

namespace App\Form;

use App\Entity\CirculationPayment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CirculationPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sum', IntegerType::class, [
                'label' => 'Операція',
            ])
            ->add('note', TextType::class, [
                'label' => 'Коментар',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CirculationPayment::class,
        ]);
    }
}
