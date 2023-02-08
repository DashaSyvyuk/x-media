<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class DeliveryTypeAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('title', TextType::class, [
                'label' => 'Назва',
                'required' => true
            ])
            ->add('cost', TextType::class, [
                'label' => 'Вартість',
                'required' => true
            ])
            ->add('enabled', CheckboxType::class, [
                'label'    => 'Активний',
                'required' => false,
            ])
            ->add('paymentTypes', null, ['label' => 'Способи оплати'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('title')
            ->add('cost');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title', null, ['label' => 'Назва'])
            ->add('cost', null, ['label' => 'Вартість'])
            ->add('enabled', null, ['label' => 'Активний'])
        ;
    }
}
