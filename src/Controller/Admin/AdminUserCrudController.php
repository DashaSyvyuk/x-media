<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\Security;

/**
 * @extends AbstractCrudController<AdminUser>
 */
#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class AdminUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminUser::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Адмін')
            ->setEntityLabelInPlural('Адміни')
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('email', 'Логін');
        yield TextField::new('password', 'Пароль')->hideOnIndex();
        yield TextField::new('name', 'Ім\'я');
        yield TextField::new('surname', 'Прізвище');
        yield TextField::new('phone', 'Телефон');
        yield ChoiceField::new('roles', 'Ролі')
            ->setChoices(AdminUser::ROLES)
            ->allowMultipleChoices(true)
        ;
    }
}
