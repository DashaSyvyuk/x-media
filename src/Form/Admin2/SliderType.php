<?php

namespace App\Form\Admin2;

use App\Entity\Promotion;
use App\Entity\Slider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class SliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'    => 'Заголовок',
                'required' => false,
            ])
            ->add('url', TextType::class, ['label' => 'Посилання'])
            ->add('priority', IntegerType::class, [
                'label'    => 'Пріоритет',
                'required' => false,
            ])
            ->add('active', SwitchType::class, [
                'label'     => 'Показувати',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('promotion', EntityType::class, [
                'class'         => Promotion::class,
                'label'         => 'Акція',
                'placeholder'   => 'Без акції',
                'required'      => false,
                'choice_label'  => 'title',
            ])
            ->add('imageFile', FileType::class, [
                'label'       => 'Картинка',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'maxSize'   => '5M',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Slider::class,
        ]);
    }
}
