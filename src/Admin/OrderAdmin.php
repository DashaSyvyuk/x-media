<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class OrderAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class, [
                'label' => 'Ім\'я',
                'required' => true
            ])
            ->add('surname', TextType::class, [
                'label' => 'Прізвище',
                'required' => true
            ])
            ->add('address', TextType::class, [
                'label' => 'Адреса',
                'required' => true
            ])
            ->add('phone', TextType::class, [
                'label' => 'Номер телефону',
                'required' => true
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => true
            ])
            ->add('paytype', TextType::class, [
                'label' => 'Спосіб оплати',
                'required' => true
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Новий' => 'Новий',
                    'В обробці' => 'В обробці',
                    'Очікує відправлення' => 'Очікує відправлення',
                    'В дорозі' => 'В дорозі',
                    'Доставлено' => 'Доставлено'
                ],
                'label' => 'Статус',
                'required' => true
            ])
            ->add('comment')
            ->add('total')
            ->add('items', CollectionType::class, [
                'type_options' => [
                    'delete' => true
                ],
                'label' => 'Товари'
            ], [
                'edit' => 'inline',
                'inline' => 'table'
            ])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('surname')
            ->add('email')
            ->add('phone')
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name', TextType::class, ['label' => 'Ім\'я'])
            ->add('surname', TextType::class, ['label' => 'Прізвище'])
            ->add('email', TextType::class, ['label' => 'Email'])
            ->add('phone', TextType::class, ['label' => 'Номер телефону'])
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                ]
            ])
        ;
    }
}
