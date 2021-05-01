<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class ProductAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('title', TextType::class, [
                'label' => 'Назва',
                'required' => true
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'ACTIVE'   => 'ACTIVE',
                    'DISABLED' => 'DISABLED'
                ],
                'label' => 'Статус'
            ])
            ->add('category', ModelType::class,  [
                'placeholder' => 'Оберіть категорію',
            ])
            ->add('description', TextareaType::class)
            ->add('images', CollectionType::class)
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
            ->add('status', null, ['label' => 'Статус'])
            ->add('category', null, ['label' => 'Категорія'])
        ;
    }
}
