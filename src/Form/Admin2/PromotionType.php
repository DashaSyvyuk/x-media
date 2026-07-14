<?php

namespace App\Form\Admin2;

use App\Entity\Promotion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('description', TextareaType::class, ['label' => 'Опис'])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help'  => '/promotion/{slug}',
            ])
            ->add('status', ChoiceType::class, [
                'label'    => 'Статус',
                'choices'  => array_flip(Promotion::STATUSES),
                'required' => true,
            ])
            ->add('activeFrom', DateTimeType::class, [
                'label'  => 'Активна з',
                'widget' => 'single_text',
            ])
            ->add('activeTo', DateTimeType::class, [
                'label'  => 'Активна до',
                'widget' => 'single_text',
            ])
            ->add('products', CollectionType::class, [
                'entry_type'    => PromotionProductEntryType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'label'         => false,
                'entry_options' => ['label' => false],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promotion::class,
        ]);
    }
}
