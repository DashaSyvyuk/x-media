<?php

namespace App\Form\Admin2;

use App\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShopSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'attr'  => $options['is_edit'] ? ['readonly' => 'readonly'] : [],
            ])
            ->add('value', TextareaType::class, [
                'label'    => 'Значення',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
            'is_edit'    => false,
        ]);
    }
}
