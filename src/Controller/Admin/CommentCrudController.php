<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Коментар')
            ->setEntityLabelInPlural('Коментарі')
            ->setSearchFields(['email', 'author', 'product.title'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('author')->setLabel('Автор');
        yield TextField::new('email', null)->setLabel('Email');
        yield ChoiceField::new('status')->setChoices([
                'Новий' => 'NEW',
                'Підтверджено' => 'CONFIRMED',
                'Заблокований' => 'DISABLED',
            ])->setLabel('Статус');
        yield TextareaField::new('comment')->setLabel('Коментар')->hideOnIndex();
        yield TextareaField::new('answer')->setLabel('Відповідь')->hideOnIndex();
        yield AssociationField::new('product')->setLabel('Товар');
    }
}
