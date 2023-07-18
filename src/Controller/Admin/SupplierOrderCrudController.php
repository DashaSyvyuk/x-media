<?php

namespace App\Controller\Admin;

use App\Entity\SupplierOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SupplierOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SupplierOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Замовлення постачальнику')
            ->setEntityLabelInPlural('Замовлення постачальникам')
            ->setSearchFields([])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->setColumns(6);
        yield AssociationField::new('supplier', 'Постачальник')->setColumns(6);
        yield TextField::new('orderNumber', 'Номер замовлення')->setColumns(6);
        yield DateField::new('expectedDate', 'Очікувана дата')->setColumns(6);

        /*yield CollectionField::new('products', 'Товари')
            ->setColumns(12)
            ->setEntryType(SupplierProductType::class)
            ->renderExpanded()
            ->onlyOnForms();*/
    }
}
