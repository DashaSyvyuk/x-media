<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, ['label' => 'Автор'])
            ->add('email')
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Новий' => 'NEW',
                    'Підтверджено' => 'CONFIRMED',
                    'Заблокований' => 'DISABLED',
                ],
                'label' => 'Статус'
            ])
            ->add('comment')
            ->add('answer')
            ->add('product')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
