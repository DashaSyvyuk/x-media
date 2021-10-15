<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class FilterAttributeAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('value', TextType::class, ['label' => 'Значення', 'required' => true])
            ->add('priority')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('value');
    }
}
