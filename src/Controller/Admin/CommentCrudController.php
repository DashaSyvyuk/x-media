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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
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
        yield TextField::new('author', 'Автор');
        yield TextField::new('email','Email');
        yield ChoiceField::new('status', 'Статус')->setChoices(Comment::STATUSES);
        yield TextareaField::new('comment', 'Коментар')->hideOnIndex();
        yield TextareaField::new('answer', 'Відповідь')->hideOnIndex();
        yield AssociationField::new('product', 'Товар');
        yield AssociationField::new('productRating', 'Рейтинг')->hideOnIndex()->setDisabled();
    }
}
