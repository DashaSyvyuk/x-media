<?php

namespace App\Controller\Admin;

use App\Entity\RozetkaProduct;
use App\Form\ProductCharacteristicType;
use App\Form\RozetkaCharacteristicType;
use App\Service\GenerateRozetkaXmlService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class RozetkaProductCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator, private readonly GenerateRozetkaXmlService $generateRozetkaXmlService) {

    }

    public static function getEntityFqcn(): string
    {
        return RozetkaProduct::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addWebpackEncoreEntry('admin');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Товари')
            ->setEntityLabelInPlural('Товар')
            ->setSearchFields(['product.id', 'title', 'product.category.title'])
            ->setDefaultSort(['product.id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->overrideTemplate('crud/field/boolean', 'admin/fields/boolean.html.twig')
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->add(Crud::PAGE_INDEX, Action::new('rozetkaXml', 'Rozetka feed *.xml')
             ->linkToCrudAction('rozetkaXmlAction')
             ->createAsGlobalAction());

        return parent::configureActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('product.id', 'Id')->hideOnForm();
        yield TextField::new('title', 'Назва')->setColumns(6);
        yield NumberField::new('stock_quantity', 'Кількість')
            ->setThousandsSeparator(' ')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextField::new('article', 'Артикул')->setColumns(6);
        yield TextField::new('series', 'Серія')->setColumns(6);
        yield NumberField::new('price', 'Ціна')
            ->setThousandsSeparator(' ')
            ->setColumns(6)
            ->hideOnIndex();
        yield BooleanField::new('ready', 'Готовий')->setColumns(7);
        yield BooleanField::new('active', 'Активний')
            ->setCustomOption('dependent', true)
            ->setDisabled(!($pageName === Crud::PAGE_INDEX) && $this->isDisabled())
            ->setColumns(7);
        yield TextareaField::new('description', 'Опис укр')
            ->setFormType(CKEditorType::class)
            ->setFormTypeOptions(
                [
                    'config' => [
                        'toolbar' => 'full',
                        'extraPlugins' => 'templates',
                        'rows' => '40',

                    ],
                    'attr' => ['rows' => '40'],
                ])
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addPanel('Характеристики');
        yield CollectionField::new('values', 'Характеристики')
            ->setColumns(12)
            ->setEntryType(RozetkaCharacteristicType::class)
            ->renderExpanded()
            ->onlyOnForms();

        /*yield FormField::addPanel('Характеристики Товару');
        yield CollectionField::new('product.characteristics', 'Характеристики товару')
            ->setColumns(12)
            ->setEntryType(ProductCharacteristicType::class)
            ->renderExpanded()
            ->onlyOnForms()
            ->setDisabled()
        ;*/
    }

    public function rozetkaXmlAction(AdminContext $adminContext): RedirectResponse
    {
        $this->generateRozetkaXmlService->execute();

        $this->addFlash('success', 'Document is generated <a href="/rozetka/products.xml" target="_blank">here</a>');

        return $this->redirect($this->adminUrlGenerator->setController(RozetkaProductCrudController::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function isDisabled(): bool
    {
        /** @var RozetkaProduct $rozetkaProduct */
        $rozetkaProduct = $this->getContext()?->getEntity()->getInstance();

        if ($rozetkaProduct && $rozetkaProduct->getId()) {
            return ! $rozetkaProduct->getReady();
        }

        return true;
    }
}
