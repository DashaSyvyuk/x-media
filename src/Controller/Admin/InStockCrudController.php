<?php

namespace App\Controller\Admin;

use App\Entity\InStock;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @extends AbstractCrudController<InStock>
 */
#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")]
class InStockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InStock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Залишок на складі')
            ->setEntityLabelInPlural('Залишки на складах')
            ->setPageTitle(Crud::PAGE_INDEX, 'Залишки')
            ->setSearchFields(['product.title', 'warehouse.title'])
            ->setDefaultSort([
                'warehouse.title' => 'ASC',
                'product.title'   => 'ASC',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->setColumns(7);

        yield AssociationField::new('product', 'Товар')
            ->setColumns(7)
            ->autocomplete()
            ->setRequired(true);

        yield AssociationField::new('warehouse', 'Склад')
            ->setColumns(7)
            ->autocomplete()
            ->hideOnIndex()
            ->setRequired(true);

        yield AssociationField::new('warehouse', 'Склад')
            ->onlyOnIndex()
            ->setCrudController(WarehouseCrudController::class)
            ->formatValue(function ($v, ?object $e) {
                $w = $e?->getWarehouse();
                if (!$w) {
                    return '—';
                }

                $city  = trim((string) ($w->getCity()  ?? ''));
                $title = trim((string) ($w->getTitle() ?? ''));

                if ($city === '' && $title === '') {
                    return '—';
                }
                if ($city === '') {
                    return $title;
                }
                if ($title === '') {
                    return $city;
                }

                return $city . ' (' . $title . ')';
            });

        yield IntegerField::new('quantity', 'Кількість')
            ->setColumns(7)
            ->setFormTypeOption('attr', ['min' => 0, 'step' => 1]);

        yield NumberField::new('purchasePrice', 'Закупівельна ціна')
            ->setColumns(7)
            ->setFormTypeOption('attr', ['min' => 0, 'step' => 1])
            ->formatValue(fn() => null)
            ->setThousandsSeparator(' ');

        yield TextareaField::new('note', 'Нотатка')
            ->hideOnIndex()
            ->setColumns(12);
    }
}
