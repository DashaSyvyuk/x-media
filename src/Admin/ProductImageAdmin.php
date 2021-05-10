<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProductImageAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $image = $this->getSubject();

        $fileFormOptions = ['required' => false, 'label' => 'Зображення'];

        if ($image && ($webPath = $image->getImageUrl())) {
            $request = $this->getRequest();
            $fullPath = $request->getBasePath() . '/' . $webPath;

            $fileFormOptions['help'] = '<img src="' . $fullPath . '" class="admin-preview" style="width: 400px; height: auto;"/>';
            $fileFormOptions['help_html'] = true;
        }

        $form
            ->add('file', FileType::class, $fileFormOptions)
            ->add('position', null, ['label' => 'Пріоритет'])
            ->add('product')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {

    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('imageUrl', null, ['label' => 'Картинка'])
            ->add('position', null, ['label' => 'Пріоритет'])
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                ]
            ])
        ;
    }
}
