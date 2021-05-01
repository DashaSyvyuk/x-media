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
    protected function configureFormFields(FormMapper $form): void
    {
        $form
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
                    'Активний' => 'ACTIVE',
                    'Заблокований' => 'DISABLED'
                ],
                'label' => 'Статус'
            ])
            ->add('position', null, ['label' => 'Пріоритет'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('title');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title', null, ['label' => 'Назва'])
            ->add('slug', null, ['label' => 'Slug'])
            ->add('status', null, ['label' => 'Статус'])
            ->add('position', null, ['label' => 'Пріоритет'])
        ;
    }
}
