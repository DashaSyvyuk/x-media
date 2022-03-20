<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CommentAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Новий' => 'NEW',
                    'Підтверджено' => 'CONFIRMED',
                    'Заблокований' => 'DISABLED'
                ],
                'label' => 'Статус'
            ])
            ->add('author', null, ['label' => 'Автор', 'required' => true])
            ->add('email', null, ['label' => 'Email'])
            ->add('comment', null, ['label' => 'Коментар', 'required' => true])
            ->add('answer', null, ['label' => 'Відповідь'])
            ->add('product', null, ['label' => 'Товар', 'disabled' => true])
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('author', null, ['label' => 'Автор'])
            ->add('email', null, ['label' => 'Email'])
            ->add('status', null, ['label' => 'Статус'])
            ->add('comment', null, ['label' => 'Коментар'])
            ->add('product', null, ['label' => 'Товар'])
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                ]
            ])
        ;
    }
}
