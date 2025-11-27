<?php

namespace App\Controller\Admin;

use App\Entity\Warranty;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @extends AbstractCrudController<Warranty>
 */
#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class WarrantyCrudController extends AbstractCrudController
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }
    public static function getEntityFqcn(): string
    {
        return Warranty::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices(array_flip(Warranty::STATUSES)))
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Гарантія')
            ->setEntityLabelInPlural('Гарантії')
            ->setSearchFields(['name', 'surname', 'phone', 'email', 'status'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield DateField::new('createdAt', 'Дата Створення')
            ->setColumns(3)
            ->setDisabled()
            ->hideOnIndex();
        yield ChoiceField::new('status', 'Статус')
            ->setChoices(array_flip(Warranty::STATUSES))
            ->renderAsBadges([
                Warranty::STATUS_NEW => 'warning',
                Warranty::STATUS_RECEIVED_FROM_CLIENT => 'warning',
                Warranty::STATUS_PROCESSING_IN_SERVICE => 'primary',
                Warranty::STATUS_FIXED => 'primary',
                Warranty::STATUS_SENT_BY_NOVA_POSHTA => 'danger',
                Warranty::STATUS_COMPLETED => 'success',
                Warranty::STATUS_NOT_FIXED => 'secondary',
                Warranty::STATUS_NOT_FIXED_RETURNED => 'secondary',
            ])
            ->setColumns(4);
        yield TextField::new('name', 'Ім\'я')->setColumns(7);
        yield TextField::new('surname', 'Прізвище')->setColumns(7);
        yield TextField::new('phone', 'Номер телефону')->setColumns(7);
        yield TextField::new('email', 'Email')->hideOnIndex()->setColumns(7);
        yield TextField::new('fromClientTtn', 'Від клієнта ТТН')->hideOnIndex()->setColumns(7);
        yield TextField::new('toClientTtn', 'До клієнта ТТН')->hideOnIndex()->setColumns(7);
        yield TextField::new('supplierOrderNumber', 'Номер замовлення постачальника')->hideOnIndex()->setColumns(7);
        yield TextField::new('orderNumber', 'Номер замовлення')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('supplier', 'Потачальник')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('product', 'Продукт')->hideOnIndex()->setColumns(7);
        yield NumberField::new('expenses', 'Витрати (грн)')
            ->setThousandsSeparator(' ')
            ->setColumns(7);
        yield TextareaField::new('reason', 'Причина')->setColumns(7)->hideOnIndex();
    }
}
