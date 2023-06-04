<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\OrderItemType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
        yield TextField::new('orderNumber', 'Номер замовлення');
        yield TextField::new('name', 'Ім\'я');
        yield TextField::new('surname', 'Прізвище');
        yield TextField::new('phone', 'Номер телефону');
        yield TextField::new('email', 'Email');
        yield TextField::new('comment', 'Коментар')->hideOnIndex();

        yield FormField::addPanel('Інформація про доставку');

        yield TextField::new('address', 'Адреса')->hideOnIndex();
        yield TextField::new('paytype', 'Спосіб оплати')->hideOnIndex();
        yield TextField::new('deltype', 'Спосіб доставки')->hideOnIndex();
        yield ChoiceField::new('status', 'Статус')->setChoices(array_flip(Order::STATUSES));
        yield BooleanField::new('paymentStatus', 'Статус оплати')->hideOnIndex();
        yield TextField::new('ttn', 'ТТН')->hideOnIndex();
        yield NumberField::new('total', 'Загальна вартість')->hideOnIndex();

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
