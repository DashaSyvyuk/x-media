<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\OrderItemType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Замовлення')
            ->setEntityLabelInPlural('Замовлення')
            ->setSearchFields(['orderNumber', 'surname', 'phone', 'email', 'status'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Контактна інформація');
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('orderNumber', 'Номер замовлення')->setColumns(7);
        yield TextField::new('name', 'Ім\'я')->setColumns(7);
        yield TextField::new('surname', 'Прізвище')->setColumns(7);
        yield TextField::new('phone', 'Номер телефону')->setColumns(7);
        yield TextField::new('email', 'Email')->setColumns(7);
        yield TextField::new('comment', 'Коментар')->hideOnIndex()->setColumns(7);

        yield FormField::addPanel('Інформація про доставку');

        yield TextField::new('address', 'Адреса')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('paytype', 'Спосіб оплати')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('deltype', 'Спосіб доставки')->hideOnIndex()->setColumns(7);
        yield ChoiceField::new('status', 'Статус')->setChoices(array_flip(Order::STATUSES))->setColumns(7);
        yield BooleanField::new('paymentStatus', 'Статус оплати')->hideOnIndex()->setColumns(7);
        yield TextField::new('ttn', 'ТТН')->hideOnIndex()->setColumns(7);
        yield NumberField::new('total', 'Загальна вартість')->hideOnIndex()->setColumns(7);

        yield FormField::addPanel('Товари');

        yield CollectionField::new('items', 'Товари')
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex(true)
            ->setEntryType(OrderItemType::class)
            ->setFormTypeOptions([
                'by_reference' => 'false'
            ])
            ->hideOnIndex();
    }
}
