<?php

namespace App\Controller\Admin;

use App\Entity\Debtor;
use App\Form\DebtorPaymentType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class DebtorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Debtor::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addCssFile('css/admin.css');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Борг')
            ->setEntityLabelInPlural('Борги')
            ->setPaginatorPageSize(30)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Ім\'я')->setColumns(4);
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
            ->setEntryType(DebtorPaymentType::class)
            ->setColumns(7)
            ->hideOnIndex()
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (0 === count($searchDto->getSort())) {
            $queryBuilder
                ->addSelect('(SELECT SUM(payment.sum) FROM App\Entity\DebtorPayment payment WHERE payment.debtor=entity) AS HIDDEN total')
                ->addOrderBy('total', 'DESC');
        }

        return $queryBuilder;
    }
}
