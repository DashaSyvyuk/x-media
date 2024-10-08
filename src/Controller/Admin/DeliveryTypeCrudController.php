<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class DeliveryTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Спосіб доставки')
            ->setEntityLabelInPlural('Способи доставки')
            ->setSearchFields(['title'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Назва');
        yield IntegerField::new('cost', 'Вартість');
        yield BooleanField::new('enabled', 'Активний');
        yield BooleanField::new('needAddressField', 'Показувати поле з адресою')->hideOnIndex();
        yield BooleanField::new('isNovaPoshta', 'Нова Пошта')->hideOnIndex();
        yield TextField::new('address', 'Адреса')->hideOnIndex();
        yield IntegerField::new('priority', 'Пріоритет')->hideOnIndex();
        yield AssociationField::new('paymentTypes', 'Способи оплати')->hideOnIndex();
        yield ImageField::new('icon')
            ->setLabel('Іконка')
            ->setUploadDir('/public/images/delivery/')
            ->setBasePath('images/delivery/');
    }
}
