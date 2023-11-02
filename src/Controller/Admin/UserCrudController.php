<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\OrderType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Користувач')
            ->setEntityLabelInPlural('Користувачі')
            ->setSearchFields(['phone', 'email', 'name', 'surname'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Ім\'я')->setColumns(7);
        yield TextField::new('surname', 'Прізвище')->setColumns(7);
        yield TextField::new('phone', 'Номер телефону')->setColumns(7);
        yield TextField::new('email', 'Email')->setColumns(7)->hideOnIndex();
        yield TextField::new('address', 'Адреса')->hideOnIndex()->setColumns(7);
        yield TextField::new('novaPoshtaCity', 'Місто доставки Новою Поштою')->hideOnIndex()->setColumns(7);
        yield TextField::new('novaPoshtaOffice', 'Відділення доставки Новою Поштою')->hideOnIndex()->setColumns(7);
        yield BooleanField::new('confirmed', 'Підтверджено Email')->hideOnIndex()->setColumns(7);

        yield CollectionField::new('orders', 'Замовлення')
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex(true)
            ->setEntryType(OrderType::class)
            ->setFormTypeOptions([
                'by_reference' => 'false'
            ])
            ->hideOnIndex();
    }
}
