<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class CategoryAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('title', TextType::class, [
                'label' => 'Назва',
                'required' => true
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'required' => true
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'ACTIVE'   => 'ACTIVE',
                    'DISABLED' => 'DISABLED'
                ],
                'label' => 'Статус'
            ])
            ->add('position', null, ['label' => 'Пріоритет'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper->add('title');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('title', null, ['label' => 'Назва'])
            ->add('slug', null, ['label' => 'Slug'])
            ->add('status', null, ['label' => 'Статус'])
            ->add('position', null, ['label' => 'Пріоритет'])
        ;
    }
}
