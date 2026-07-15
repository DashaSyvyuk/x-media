<?php

namespace App\Form\Admin2;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Bootstrap 5 switch for logical on/off fields mapped to custom values.
 */
class SwitchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $onValue  = $options['on_value'];
        $offValue = $options['off_value'];

        $builder->addModelTransformer(new CallbackTransformer(
            fn(mixed $value): bool => $value === $onValue,
            fn(?bool $checked): mixed => $checked ? $onValue : $offValue,
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required'     => false,
            'false_values' => [null, '', false],
            'row_attr'     => ['class' => 'admin2-switch-row'],
        ]);

        $resolver->setRequired(['on_value', 'off_value']);
        $resolver->setAllowedTypes('on_value', ['string', 'int', 'bool']);
        $resolver->setAllowedTypes('off_value', ['string', 'int', 'bool']);
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'admin2_switch';
    }
}
