<?php

namespace App\Controller\Admin;

use App\Entity\Supplier;
use App\Form\SupplierProductType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SupplierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Supplier::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Постачальник')
            ->setEntityLabelInPlural('Постачальники')
            ->setSearchFields(['title', 'name', 'surname', 'email'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->setColumns(6);
        yield TextField::new('title', 'Заголовок')->setColumns(6);
        yield TextField::new('name', 'Ім\'я')->setColumns(6);
        yield TextField::new('surname', 'Прізвище')->setColumns(6);
        yield TextField::new('phone', 'Номер телефону')->setColumns(6);
        yield EmailField::new('email', 'Email')->setColumns(6);
        yield TextareaField::new('address', 'Адреса')->hideOnIndex()->setColumns(6);
        yield TextField::new('bankAccount', 'Рахунок')->hideOnIndex()->setColumns(6);
        yield AssociationField::new('currency', 'Валюта')->hideOnIndex()->setColumns(6);
        yield BooleanField::new('active', 'Активний')->setColumns(6);
        yield CollectionField::new('products', 'Товари')
            ->setColumns(12)
            ->setEntryType(SupplierProductType::class)
            ->renderExpanded()
            ->onlyOnForms();
    }
}
