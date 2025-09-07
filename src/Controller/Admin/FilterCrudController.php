<?php

namespace App\Controller\Admin;

use App\Entity\Filter;
use App\Form\AttributeType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')")]
class FilterCrudController extends AbstractCrudController
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }

    public static function getEntityFqcn(): string
    {
        return Filter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Фільтр')
            ->setEntityLabelInPlural('Фільтри')
            ->setSearchFields(['title', 'category.title'])
            ->setDefaultSort(['title' => 'ASC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Фільтр');

        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title')->setLabel('Назва параметру');
        yield AssociationField::new('category')->setLabel('Категорія');
        yield NumberField::new('priority')->setLabel('Пріоритет');
        yield NumberField::new('openedCount')->setLabel('Кількість відкритих параметрів');
        yield BooleanField::new('isOpened')->setLabel('Закритий');

        yield FormField::addPanel('Параметри');

        yield CollectionField::new('attributes', 'Параметри')
            ->renderExpanded()
            ->setEntryType(AttributeType::class)
            ->hideOnIndex();
    }
}
