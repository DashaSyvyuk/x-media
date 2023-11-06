<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\OrderItemType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use SM\Factory\Factory;
use SM\SMException;

class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Factory $stateFactory,
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices(array_flip($this->getAvailableStatuses())))
            ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addCssFile('admin/admin.css');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Замовлення')
            ->setEntityLabelInPlural('Замовлення')
            ->setSearchFields(['orderNumber', 'surname', 'phone', 'email', 'status'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Контактна інформація');
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('orderNumber', 'Номер замовлення')->setColumns(7);
        yield TextField::new('name', 'Ім\'я')->setColumns(7);
        yield TextField::new('surname', 'Прізвище')->setColumns(7);
        yield TextField::new('phone', 'Номер телефону')->setColumns(7);
        yield TextField::new('email', 'Email')->setColumns(7)->hideOnIndex();
        yield TextField::new('comment', 'Коментар')->hideOnIndex()->setColumns(7);

        yield FormField::addPanel('Інформація про доставку');

        yield TextField::new('address', 'Адреса')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('paytype', 'Спосіб оплати')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('deltype', 'Спосіб доставки')->hideOnIndex()->setColumns(7);
        yield ChoiceField::new('status', 'Статус')
            ->setChoices(array_flip($this->getAvailableStatuses()))
            ->renderAsBadges([
                Order::NEW => 'warning',
                Order::NOT_APPROVED => 'warning',
                Order::NOVA_POSHTA_DELIVERING => 'danger',
                Order::COURIER_DELIVERING => 'danger',
                Order::COMPLETED => 'success',
                Order::PACKING => 'info',
                Order::APPROVED => 'primary',
                Order::CANCELED_NOT_CONFIRMED => 'secondary',
                Order::CANCELED_NO_PRODUCT => 'secondary',
                Order::CANCELED_NOT_PICKED_UP => 'secondary',
            ])
            ->setColumns(7);
        yield BooleanField::new('paymentStatus', 'Статус оплати')->hideOnIndex()->setColumns(7);
        yield TextField::new('ttn', 'ТТН')->hideOnIndex()->setColumns(7);
        yield NumberField::new('total', 'Загальна вартість')
            ->setThousandsSeparator(' ')
            ->setColumns(7);

        yield FormField::addPanel('Товари');

        yield CollectionField::new('items', 'Товари')
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex(true)
            ->setEntryType(OrderItemType::class)
            ->setFormTypeOptions([
                'by_reference' => 'false'
            ])
            ->hideOnIndex();
    }

    private function getAvailableStatuses(): array
    {
        $currentOrder = $this->getContext()?->getEntity()?->getInstance();
        if ($currentOrder) {
            $statuses = [];
            $orderSM = $this->stateFactory->get($currentOrder, 'simple');
            foreach (Order::STATUSES as $key => $status) {
                try {
                    if ($orderSM->can($key)) {
                        $statuses[$key] = $status;
                    }
                } catch (SMException $e) {
                }
            }

            return $statuses;
        }

        return Order::STATUSES;
    }
}
