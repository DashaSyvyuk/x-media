<?php

namespace App\Controller\Admin;

use App\Entity\Circulation;
use App\Form\CirculationPaymentType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RequestStack;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class CirculationCrudController extends AbstractCrudController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getEntityFqcn(): string
    {
        return Circulation::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addCssFile('css/admin.css');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Обіг')
            ->setEntityLabelInPlural('Обіг')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $request = $this->requestStack->getCurrentRequest();
        $showAll = $request->query->getBoolean('showAll');

        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_DETAIL) {
            $circulation = $this->getContext()->getEntity()->getInstance();
            if (method_exists($circulation, 'setShowAllPayments')) {
                $circulation->setShowAllPayments($showAll);
            }
        }

        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('adminUser', 'Email адміна')->setColumns(4);

        yield AssociationField::new('currency', 'Валюта')->setColumns(4);

        yield TextField::new('total', 'Борг')
            ->formatValue(function ($value) {
                $value = number_format($value, 0, '.', ' ');
                return $value >= 0
                    ? '<span class="green">' . $value . '</span>'
                    : '<span class="red">' . $value . '</span>';
            })
            ->setColumns(4)
            ->setDisabled();

        yield CollectionField::new('payments', $showAll ? 'Всі операції' : 'Останні 30 операцій')
            ->allowAdd()
            ->allowDelete()
            ->setEntryType(CirculationPaymentType::class)
            ->renderExpanded(false)
            ->setColumns(7)
            ->hideOnIndex()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('active', 'Активний'));
    }
}
