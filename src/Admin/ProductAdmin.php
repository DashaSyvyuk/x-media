<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\Form\Type\CollectionType;

final class ProductAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Загальна інформація')
                ->add('title', TextType::class, [
                    'label' => 'Назва',
                    'required' => true
                ])
                ->add('status', ChoiceType::class, [
                    'choices' => [
                        'Активний' => 'ACTIVE',
                        'Заблокований' => 'DISABLED'
                    ],
                    'label' => 'Статус'
                ])
                ->add('price')
                ->add('category', ModelType::class,  [
                    'placeholder' => 'Оберіть категорію',
                ])
                ->add('description', TextareaType::class)
            ->end()
            ->with('Зображення')
                ->add('images', CollectionType::class, [
                    'type_options' => [
                        'delete' => true
                    ],
                    'label' => 'Зображення'
                ], [
                    'edit' => 'inline',
                    'inline' => 'table'
                ])
            ->end()
            ->with('Характеристики')
                ->add('characteristics', CollectionType::class, [
                    'type_options' => [
                        'delete' => true
                    ],
                    'label' => 'Характеристики'
                ], [
                    'edit' => 'inline',
                    'inline' => 'table'
                ])
            ->end()
            ->with('Мета теги')
                ->add('metaKeyword', TextType::class, [
                    'label' => 'Ключові слова',
                    'required' => true
                ])
                ->add('metaDescription', TextType::class, [
                    'label' => 'Опис',
                    'required' => true
                ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('title');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title', null, ['label' => 'Назва'])
            ->add('status', null, ['label' => 'Статус'])
            ->add('category', null, ['label' => 'Категорія'])
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                ]
            ])
        ;
    }
}
