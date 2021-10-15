<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class ProductFilterAttributeAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('filter', null, ['label' => 'Фільтр'])
            ->add('filterAttribute', null, ['label' => 'Параметр'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
    }
}
