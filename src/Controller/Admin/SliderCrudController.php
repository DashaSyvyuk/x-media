<?php

namespace App\Controller\Admin;

use App\Entity\Slider;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @extends AbstractCrudController<Slider>
 */
#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class SliderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Slider::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Слайдер')
            ->setEntityLabelInPlural('Слайдери')
            ->setSearchFields(['title'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Заголовок');
        yield TextField::new('url', 'Посилання')->hideOnIndex();
        yield NumberField::new('priority', 'Пріоритет')->hideOnIndex();
        yield BooleanField::new('active', 'Показувати');
        yield AssociationField::new('promotion', 'Акція')->hideOnIndex();
        yield ImageField::new('imageUrl')
            ->setLabel('Картинка')
            ->setUploadDir('/public/images/slider/')
            ->setBasePath('images/slider/');
    }
}
