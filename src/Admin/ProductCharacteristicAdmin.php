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
            ->add('title', null, ['label' => 'Назва', 'required' => true])
            ->add('value', null, ['label' => 'Значення', 'required' => true])
            ->add('position', null, ['label' => 'Пріоритет', 'required' => true])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
    }
}
