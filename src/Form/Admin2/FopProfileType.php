<?php

namespace App\Form\Admin2;

use App\Entity\FopProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FopProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'ФОП / Назва'])
            ->add('bankAccount', TextType::class, ['label' => 'Рахунок / Банк'])
            ->add('edrpou', TextType::class, ['label' => 'ЄДРПОУ'])
            ->add('address', TextType::class, ['label' => 'Адреса'])
            ->add('note', TextareaType::class, [
                'label' => 'Нотатка',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => FopProfile::class,
            'csrf_protection'   => false,
        ]);
    }
}
