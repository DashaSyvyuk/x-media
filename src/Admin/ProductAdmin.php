<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\Form\Type\CollectionType;

final class ProductAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Загальна інформація', ['class' => 'col-md-6'])
                ->add('title', TextType::class, ['label' => 'Назва', 'required' => true])
                ->add('status', ChoiceType::class, [
                    'choices' => [
                        'Активний' => 'ACTIVE',
                        'Заблокований' => 'DISABLED'
                    ],
                    'label' => 'Статус'
                ])
                ->add('category', ModelType::class,  ['placeholder' => 'Оберіть категорію', 'required' => true])
                ->add('note', TextareaType::class, ['label' => 'Нотатки', 'required' => false])
            ->end()
            ->with('Ціни', ['class' => 'col-md-6'])
                ->add('currency', ModelType::class, ['placeholder' => 'Оберіть валюту', 'required' => true])
                ->add('priceWithVAT', NumberType::class, ['label' => 'Ціна з ПДВ (zl)', 'required' => true])
                ->add('priceWithoutVAT', NumberType::class, ['label' => 'Ціна без ПДВ (zl)', 'required' => false, 'disabled' => true])
                ->add('purchasePriceUAH', NumberType::class, [
                    'disabled' => true,
                    'required' => false,
                    'label' => 'Ціна (грн)'
                ])
                ->add('deliveryCost', null, ['label' => 'Витрати на доставку (грн)', 'required' => true])
                ->add('totalPrice', NumberType::class, [
                    'label' => 'Загальна вартість товару (грн)',
                    'disabled' => true,
                    'required' => false
                ])
                ->add('price', null, ['label' => 'Ціна (грн)', 'required' => true])
                ->add('marge', NumberType::class, [
                    'label' => 'Marge (грн)',
                    'disabled' => true,
                    'required' => false
                ])
                ->add('margePercentage', NumberType::class, [
                    'label' => 'Marge(%)',
                    'disabled' => true,
                    'required' => false
                ])
            ->end()
            ->with('Опис')
                ->add('description', TextareaType::class, [
                    'label' => 'Опис',
                    'required' => true,
                    'attr' => [
                        'class' => 'tinymce',
                        'data-theme' => "advanced"
                    ]
                ])
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
            ->with('Фільтри')
                ->add('filterAttributes', CollectionType::class, [
                    'type_options' => [
                        'delete' => true
                    ],
                    'label' => 'Фільтри'
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
            ->with('Відгуки')
                ->add('comments', CollectionType::class, [
                    'type_options' => [
                        'delete' => true
                    ],
                    'label' => 'Відгуки'
                ], [
                    'edit' => 'inline',
                    'inline' => 'table'
                ])
            ->end();
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
                    'clone' => [
                        'template' => 'Admin/CRUD/list__action_clone.html.twig',
                    ]
                ]
            ])
        ;
    }

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('clone', $this->getRouterIdParameter() . '/clone');
    }
}
