<?php

namespace App\Controller\Admin;

use App\Entity\Slider;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class SliderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Slider::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Слайдер')
            ->setEntityLabelInPlural('Слайдери')
            ->setSearchFields(['title'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title')->setLabel('Заголовок');
        yield TextField::new('url')->setLabel('Посилання')->hideOnIndex();
        yield NumberField::new('priority')->setLabel('Пріоритет')->hideOnIndex();
        yield ImageField::new('imageUrl')
            ->setLabel('Картинка')
            ->setUploadDir('/public/images/slider/')
            ->setBasePath('images/slider/');
    }
}
