<?php

namespace App\Admin;

use App\Admin\Form\Type\FileUploadType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class ProductImageAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('imageUrl')
            ->add('position')
        ;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->add('imageUrl')
            ->add('position')
            ->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper):void
    {
        $formMapper
            ->add('imageUrl', FileUploadType::class, [
                'required' => true,
                'label'    => 'Upload Image',
                'uppy'     => [
                    'uppy' => [
                        'restrictions' => [
                            'maxNumberOfFiles' => 1,
                            'minNumberOfFiles' => 1,
                            'allowedFileTypes' => [
                                'image/*',
                            ],
                        ]
                    ]
                ],
            ])
            ->add('position')
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('imageUrl')
            ->add('position')
        ;
    }
}
