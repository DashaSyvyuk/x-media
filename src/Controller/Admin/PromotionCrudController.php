<?php

namespace App\Controller\Admin;

use App\Entity\Promotion;
use App\Form\PromotionProductType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PromotionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Promotion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Акція')
            ->setEntityLabelInPlural('Акції')
            ->setSearchFields(['title'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Назва')->setColumns(7);
        yield TextareaField::new('description', 'Опис')->hideOnIndex()->setColumns(7);
        yield TextField::new('slug', 'Slug (/promotion/{slug})')->hideOnIndex()->setColumns(7);
        yield ChoiceField::new('status', 'Статус')
            ->setChoices(array_flip(Promotion::STATUSES))
            ->renderAsBadges([
                Promotion::ACTIVE  => 'success',
                Promotion::BLOCKED => 'danger'
            ])
            ->setColumns(7);
        yield FormField::addPanel('Дати');
        yield DateTimeField::new('activeFrom', 'Активна з')->setColumns(3);
        yield DateTimeField::new('activeTo', 'Активна до')->setColumns(3);

        yield CollectionField::new('products', 'Товари')
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryType(PromotionProductType::class)
            ->hideOnIndex()
            ->setColumns(7)
        ;
    }
}
