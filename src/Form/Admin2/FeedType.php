<?php

namespace App\Form\Admin2;

use App\Entity\Feed;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label'   => 'Тип',
                'choices' => array_flip(Feed::TYPES),
            ])
            ->add('cutCharacteristics', SwitchType::class, [
                'label'     => 'Обрізати характеристики до 255 символів',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('ignoreBrands', TextType::class, [
                'label'    => 'Ігнорувати бренди',
                'required' => false,
                'help'     => 'Розділення \';\'',
            ])
            ->add('ourPercent', IntegerType::class, ['label' => 'Our percent'])
            ->add('fee', IntegerType::class, ['label' => 'Fee'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feed::class,
        ]);
    }
}
