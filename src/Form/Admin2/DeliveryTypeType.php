<?php

namespace App\Form\Admin2;

use App\Entity\DeliveryType;
use App\Entity\PaymentType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class DeliveryTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('cost', IntegerType::class, [
                'label'    => 'Вартість',
                'required' => false,
            ])
            ->add('enabled', SwitchType::class, [
                'label'     => 'Активний',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('needAddressField', SwitchType::class, [
                'label'     => 'Показувати поле з адресою',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('isNovaPoshta', SwitchType::class, [
                'label'     => 'Нова Пошта',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('address', TextareaType::class, [
                'label'    => 'Адреса',
                'required' => false,
            ])
            ->add('priority', IntegerType::class, [
                'label'    => 'Пріоритет',
                'required' => false,
            ])
            ->add('paymentTypes', EntityType::class, [
                'class'        => PaymentType::class,
                'label'        => 'Способи оплати',
                'choice_label' => 'title',
                'multiple'     => true,
                'required'     => false,
            ])
            ->add('iconFile', FileType::class, [
                'label'       => 'Іконка',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'maxSize'   => '2M',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryType::class,
        ]);
    }
}
