<?php

namespace App\Controller\Admin;

use App\Entity\ReturnProduct;
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

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class
ReturnProductCrudController extends AbstractCrudController
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }
    public static function getEntityFqcn(): string
    {
        return ReturnProduct::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices(array_flip(ReturnProduct::STATUSES)))
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Повернення')
            ->setEntityLabelInPlural('Повернення')
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
            ->setChoices(array_flip(ReturnProduct::STATUSES))
            ->renderAsBadges([
                ReturnProduct::STATUS_NEW => 'warning',
                ReturnProduct::STATUS_PROCESSING => 'warning',
                ReturnProduct::STATUS_COMPLETED => 'success',
                ReturnProduct::STATUS_REFUSED => 'secondary',
            ])
            ->setColumns(4);
        yield TextField::new('name', 'Ім\'я')->setColumns(7);
        yield TextField::new('surname', 'Прізвище')->setColumns(7);
        yield TextField::new('phone', 'Номер телефону')->setColumns(7);
        yield TextField::new('email', 'Email')->hideOnIndex()->setColumns(7);
        yield TextField::new('ttn', 'ТТН')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('supplier', 'Потачальник')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('product', 'Продукт')->hideOnIndex()->setColumns(7);
        yield NumberField::new('amount', 'Сума')
            ->setThousandsSeparator(' ')
            ->setColumns(7);
        yield TextareaField::new('reason', 'Причина')->setColumns(7);
    }
}
