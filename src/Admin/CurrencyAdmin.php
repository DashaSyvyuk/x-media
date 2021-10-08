<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

final class CurrencyAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('title', TextType::class, [
                'label' => 'Назва',
                'required' => true
            ])
            ->add('code', TextType::class, [
                'label' => 'Код',
                'required' => true
            ])
            ->add('exchangeRate', NumberType::class)
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('title')
            ->add('code');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title', null, ['label' => 'Назва'])
            ->add('code', null, ['label' => 'Код'])
            ->add('exchangeRate', null, ['label' => 'Обмінний курс'])
        ;
    }
}
