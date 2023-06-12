<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
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
        yield TextField::new('title')->setLabel('Назва');
        yield TextField::new('slug')->setLabel('Slug');
        yield ChoiceField::new('status')->setChoices([
                'Активний' => 'ACTIVE',
                'Заблокований' => 'DISABLED',
            ])->setLabel('Статус');
        yield TextareaField::new('metaKeyword')->setLabel('Ключові слова')->hideOnIndex();
        yield TextareaField::new('metaDescription')->setLabel('Опис')->hideOnIndex();
        yield IntegerField::new('position')->setLabel('Пріоритет');
    }
}
