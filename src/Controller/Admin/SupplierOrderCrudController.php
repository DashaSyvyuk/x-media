<?php

namespace App\Controller\Admin;

use App\Entity\SupplierOrder;
use App\Form\SupplierOrderProductType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @extends AbstractCrudController<SupplierOrder>
 */
#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
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

        yield CollectionField::new('products', 'Товари')
            ->setColumns(12)
            ->setEntryType(SupplierOrderProductType::class)
            ->renderExpanded()
            ->onlyOnForms();
    }
}
