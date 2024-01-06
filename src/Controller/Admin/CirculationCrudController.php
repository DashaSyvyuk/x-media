<?php

namespace App\Controller\Admin;

use App\Entity\Circulation;
use App\Form\CirculationPaymentType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CirculationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Circulation::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addCssFile('css/admin.css');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Обіг')
            ->setEntityLabelInPlural('Обіг')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('adminUser', 'Email адміна')->setColumns(4);
        yield AssociationField::new('currency', 'Валюта')->setColumns(4);
        yield TextField::new('total', 'Борг')
            ->formatValue(function ($value) {
                $value = number_format($value, 0, '.', ' ');
                return $value >= 0 ? '<span class="green">' . $value . '</span>' : '<span class="red">' . $value . '</span>';
            })
            ->setColumns(4)
            ->setDisabled();
        yield CollectionField::new('payments', 'Операції')
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded(false)
            ->setEntryType(CirculationPaymentType::class)
            ->setColumns(7)
            ->hideOnIndex()
        ;
    }
}
