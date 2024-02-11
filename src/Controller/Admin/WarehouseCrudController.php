<?php

namespace App\Controller\Admin;

use App\Entity\Warehouse;
use App\Form\ProductQueueType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class WarehouseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Warehouse::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Склад')
            ->setEntityLabelInPlural('Склади')
            ->setSearchFields(['title', 'address', 'city'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->setColumns(7);
        yield TextField::new('title', 'Заголовок')->setColumns(7);
        yield AssociationField::new('adminUser', 'Адмін')->hideOnIndex()->setColumns(7);
        yield TextareaField::new('address', 'Адреса')->hideOnIndex()->setColumns(7);
        yield TextField::new('city', 'Місто')->setColumns(7);
        yield TextareaField::new('notes', 'Нотатки')->hideOnIndex()->setColumns(7);
        yield BooleanField::new('active', 'Активний')->setColumns(7);
        yield AssociationField::new('productQueues', 'Черга товарів')->hideOnIndex();

        yield CollectionField::new('productQueues')
            ->setColumns(12)
            ->setEntryType(ProductQueueType::class)
            ->renderExpanded(false)
            ->onlyOnForms();
    }
}
