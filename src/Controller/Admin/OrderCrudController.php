<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\OrderItemType;
use App\Utils\OrderNumber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SM\Factory\Factory;
use SM\SMException;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class
OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Factory $stateFactory,
        private readonly OrderNumber $orderNumber,
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
        yield TextField::new('orderNumber', 'Номер замовлення')
            ->setFormTypeOptions([
                'data' => $this->getOrderNumber(),
            ])
            ->setColumns(5);
        yield DateField::new('createdAt', 'Дата Створення')
            ->setColumns(3)
            ->setDisabled()
            ->hideOnIndex();
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
            ->setColumns(5);
        yield TextField::new('ttn', 'ТТН')->hideOnIndex()->setColumns(3);
        yield TextField::new('name', 'Ім\'я')->hideOnIndex()->setColumns(7);
        yield TextField::new('surname', 'Прізвище')->hideOnIndex()->setColumns(7);
        yield TextField::new('phone', 'Номер телефону')->hideOnIndex()->setColumns(7);
        yield TextField::new('email', 'Email')->setColumns(7)->hideOnIndex();
        yield TextField::new('comment', 'Коментар')->setColumns(7);

        yield FormField::addPanel('Інформація про доставку');

        yield TextField::new('address', 'Адреса')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('paytype', 'Спосіб оплати')->hideOnIndex()->setColumns(7);
        yield AssociationField::new('deltype', 'Спосіб доставки')->hideOnIndex()->setColumns(7);

        yield BooleanField::new('sendNotification', 'Відправляти сповіщення')->hideOnIndex()->setColumns(7);
        yield BooleanField::new('paymentStatus', 'Статус оплати')->hideOnIndex()->setColumns(7);
        yield NumberField::new('total', 'Загальна вартість')
            ->setThousandsSeparator(' ')
            ->setDisabled()
            ->setColumns(7);
        yield ChoiceField::new('labels', 'Мітка')
            ->setChoices(Order::LABELS)
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges([
                Order::LABEL_XMEDIA => 'success',
                Order::LABEL_PROM   => 'info',
                Order::LABEL_OLX    => 'primary',
                Order::LABEL_JONNY  => 'warning'
            ])
            ->setColumns(7);

        yield FormField::addPanel('Товари');

        yield CollectionField::new('items', 'Товари')
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryType(OrderItemType::class);
    }

    private function getAvailableStatuses(): array
    {
        $currentOrder = $this->getContext()?->getEntity()->getInstance();

        if ($currentOrder && $currentOrder->getId()) {
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

    private function getOrderNumber(): string
    {
        $currentOrder = $this->getContext()?->getEntity()->getInstance();

        if ($currentOrder && $currentOrder->getId()) {
            return $currentOrder->getOrderNumber();
        }

        return $this->orderNumber->generateOrderNumber();
    }
}
