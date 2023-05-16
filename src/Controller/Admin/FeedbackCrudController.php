<?php

namespace App\Controller\Admin;

use App\Entity\Feedback;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FeedbackCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Feedback::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Відгук')
            ->setEntityLabelInPlural('Відгуки')
            ->setSearchFields(['email', 'author'])
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
    }
}
