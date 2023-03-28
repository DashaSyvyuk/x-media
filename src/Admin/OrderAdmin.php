<?php

namespace App\Admin;

use App\Entity\Order;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class OrderAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Контактна інформація', ['class' => 'col-md-6'])
                ->add('orderNumber', TextType::class, [
                    'label' => 'Номер замовлення',
                    'required' => true
                ])
                ->add('name', TextType::class, [
                    'label' => 'Ім\'я',
                    'required' => true
                ])
                ->add('surname', TextType::class, [
                    'label' => 'Прізвище',
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
                ->add('comment')
            ->end()
            ->with('Інформація про доставку', ['class' => 'col-md-6'])
                ->add('address', TextType::class, [
                    'label' => 'Адреса',
                    'required' => true
                ])
                ->add('paytype', TextType::class, [
                    'label' => 'Спосіб оплати',
                    'required' => true
                ])
                ->add('deltype', TextType::class, [
                    'label' => 'Спосіб доставки',
                    'required' => true
                ])
                ->add('status', ChoiceType::class, [
                    'choices' => Order::STATUSES,
                    'label' => 'Статус',
                    'required' => true
                ])
                ->add('paymentStatus', CheckboxType::class, [
                    'label'    => 'Статус оплати',
                    'required' => false,
                ])
                ->add('total', null, [
                    'label' => 'Загальна вартість'
                ])
            ->end()
            ->with('Товари')
                ->add('items', CollectionType::class, [
                    'type_options' => [
                        'delete' => true
                    ],
                    'label' => 'Товари'
                ], [
                    'edit' => 'inline',
                    'inline' => 'table'
                ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('orderNumber')
            ->add('name')
            ->add('surname')
            ->add('email')
            ->add('phone')
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('orderNumber', TextType::class, ['label' => 'Номер замовлення'])
            ->add('name', TextType::class, ['label' => 'Ім\'я'])
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
