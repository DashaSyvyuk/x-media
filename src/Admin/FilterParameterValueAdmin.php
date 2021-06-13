<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class FilterParameterValueAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('value', TextType::class, [
                'label' => 'Значення',
                'required' => true
            ])
            ->add('product', ModelType::class,  [
                'placeholder' => 'Оберіть товар',
            ])
            ->add('filterParameter', ModelType::class,  [
                'placeholder' => 'Оберіть параметр',
            ])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('value');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('value', null, ['label' => 'Значення'])
            ->add('products', null, ['label' => 'Товар'])
            ->add('filterParameter', null, ['label' => 'Параметр'])
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                ]
            ])
        ;
    }
}
