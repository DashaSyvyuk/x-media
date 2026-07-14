<?php

namespace App\Form\Admin2;

use App\Entity\CirculationPayment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CirculationPaymentEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sum', IntegerType::class, [
                'label' => 'Сума',
                'help'  => 'Плюс — надходження, мінус — витрата',
            ])
            ->add('note', TextType::class, [
                'label'    => 'Коментар',
                'required' => false,
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
