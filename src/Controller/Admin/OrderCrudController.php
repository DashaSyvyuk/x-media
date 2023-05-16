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
            ->setSearchFields(['orderNumber', 'surname', 'phone', 'email'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Контактна інформація');
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('orderNumber')->setLabel('Номер замовлення');
        yield TextField::new('name')->setLabel('Ім\'я');
        yield TextField::new('surname')->setLabel('Прізвище');
        yield TextField::new('phone')->setLabel('Номер телефону');
        yield TextField::new('email')->setLabel('Email');
        yield TextField::new('comment')->setLabel('Коментар')->hideOnIndex();

        yield FormField::addPanel('Інформація про доставку');

        yield TextField::new('address')->setLabel('Адреса')->hideOnIndex();
        yield TextField::new('paytype')->setLabel('Спосіб оплати')->hideOnIndex();
        yield TextField::new('deltype')->setLabel('Спосіб доставки')->hideOnIndex();
        yield ChoiceField::new('status')->setChoices([
            'Новий' => 'Новий',
            'Очікує підтвердження' => 'Очікує підтвердження',
            'В обробці' => 'В обробці',
            'Скасовано' => 'Скасовано',
            'Замовлення у постачальника' => 'Замовлення у постачальника',
            'В дорозі' => 'В дорозі',
            'Відправлено Новою Поштою' => 'Відправлено Новою Поштою',
            'Відправлено нашою доставкою' => 'Відправлено нашою доставкою',
            'Завершено' => 'Завершено'
        ])->setLabel('Статус')->hideOnIndex();
        yield BooleanField::new('paymentStatus')->setLabel('Статус оплати')->hideOnIndex();
        yield NumberField::new('total')->setLabel('Загальна вартість')->hideOnIndex();

        yield FormField::addPanel('Товари');

        yield CollectionField::new('items')
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
