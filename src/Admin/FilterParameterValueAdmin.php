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
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
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

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper->add('value');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('value', null, ['label' => 'Назва'])
            ->add('product', null, ['label' => 'Статус'])
            ->add('filterParameter', null, ['label' => 'Категорія'])
        ;
    }
}
