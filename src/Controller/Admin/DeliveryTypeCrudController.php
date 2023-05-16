<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DeliveryTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Спосіб доставки')
            ->setEntityLabelInPlural('Способи доставки')
            ->setSearchFields(['title'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title')->setLabel('Назва');
        yield TextField::new('cost')->setLabel('Вартість');
        yield BooleanField::new('enabled')->setLabel('Активний');
        yield AssociationField::new('paymentTypes')->setLabel('Способи оплати')->hideOnIndex();
    }
}
