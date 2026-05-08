<?php

namespace App\Controller\Admin;

use App\Entity\PaymentType;
use App\EventListener\ImageUploadSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @extends AbstractCrudController<PaymentType>
 */
#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class PaymentTypeCrudController extends AbstractCrudController
{
    public function __construct(private ImageUploadSubscriber $imageUploadSubscriber)
    {
    }

    public static function getEntityFqcn(): string
    {
        return PaymentType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Спосіб оплати')
            ->setEntityLabelInPlural('Способи оплати')
            ->setSearchFields(['title'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->imageUploadSubscriber->ensureLocalDirFor(PaymentType::class);

        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Назва');
        yield BooleanField::new('enabled', 'Активний');
        yield IntegerField::new('cost', 'Вартість');
        yield ImageField::new('icon')
            ->setLabel('Іконка')
            ->setUploadDir('/public/images/payment/')
            ->setBasePath($this->imageUploadSubscriber->cdnBaseUrlFor(PaymentType::class));
    }
}
