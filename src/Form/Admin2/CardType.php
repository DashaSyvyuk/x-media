<?php

namespace App\Form\Admin2;

use App\Entity\Card;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('holderName', TextType::class, ['label' => 'Ім\'я і прізвище'])
            ->add('cardNumber', TextType::class, [
                'label'       => 'Номер карти',
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => '/^\d{4}[\s]?\d{4}[\s]?\d{4}[\s]?\d{4}$/',
                        'message' => 'Введіть 16 цифр картки.',
                    ]),
                ],
            ])
            ->add('expiry', TextType::class, [
                'label'       => 'Термін дії (дд/мм)',
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => '/^\d{2}\/\d{2}$/',
                        'message' => 'Формат: дд/мм (наприклад 09/27).',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => Card::class,
            'csrf_protection' => false,
        ]);
    }
}
