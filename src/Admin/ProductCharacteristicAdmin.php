<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class ProductCharacteristicAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('title', null, ['label' => 'Назва'])
            ->add('value', null, ['label' => 'Значення'])
            ->add('position', null, ['label' => 'Пріоритет'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
    }
}
