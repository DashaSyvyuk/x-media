<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class SliderAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $slider = $this->getSubject();

        $fileFormOptions = ['required' => false, 'label' => 'Зображення'];

        if ($slider && ($webPath = $slider->getImageUrl())) {
            $request = $this->getRequest();
            $fullPath = $request->getBasePath() . '/' . $webPath;

            $fileFormOptions['help'] = '<img src="' . $fullPath . '" class="admin-preview" style="width: 400px; height: auto;"/>';
            $fileFormOptions['help_html'] = true;
        }

        $formMapper
            ->add('title', TextType::class, [
                'label' => 'Заголовок',
                'required' => false
            ])
            ->add('url', TextType::class, ['label' => 'Посилання'])
            ->add('file', FileType::class, $fileFormOptions)
            ->add('priority', null, ['label' => 'Пріоритет'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper->add('title');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('title', null, ['label' => 'Заголовок'])
            ->add('url', null, ['label' => 'Посилання'])
            ->add('priority', null, ['label' => 'Пріоритет'])
        ;
    }
}
