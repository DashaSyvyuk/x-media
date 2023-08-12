<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Категорія')
            ->setEntityLabelInPlural('Категорії')
            ->setSearchFields(['title', 'status'])
            ->setDefaultSort(['title' => 'ASC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('parent', 'Батьківська категорія')->hideOnIndex();
        yield TextField::new('title', 'Назва');
        yield ImageField::new('image', 'Картинка')
            ->setUploadDir('/public/images/category/')
            ->setBasePath('images/category/');
        yield TextField::new('slug', 'Slug');
        yield ChoiceField::new('status', 'Статус')->setChoices(Category::STATUSES);
        yield TextareaField::new('metaKeyword', 'Ключові слова')->hideOnIndex();
        yield TextareaField::new('metaDescription', 'Опис')->hideOnIndex();
        yield IntegerField::new('position', 'Пріоритет');
        yield BooleanField::new('showInHeader', 'Показувати в хедері')->hideOnIndex();
    }
}
