<?php

namespace App\Controller\Admin;

use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaProduct;
use App\Form\RozetkaCharacteristicType;
use App\Repository\RozetkaProductRepository;
use App\Service\GenerateRozetkaXmlService;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")]
class RozetkaProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly GenerateRozetkaXmlService $generateRozetkaXmlService,
        private readonly RequestStack $requestStack,
        private readonly RozetkaProductRepository $rozetkaProductRepository,
    )
    {
        ini_set('memory_limit', '512M');
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
            ->setSearchFields(['product.id', 'title', 'product.category.title', 'product.productCode'])
            ->setDefaultSort(['product.id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->overrideTemplates([
                'crud/edit' => 'admin/custom_edit.html.twig',
                'crud/field/boolean' => 'admin/fields/boolean.html.twig'
            ])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->add(Crud::PAGE_INDEX, Action::new('rozetkaXmlForA', 'Rozetka feed for A *.xml')
             ->linkToCrudAction('rozetkaForAXmlAction')
             ->createAsGlobalAction());

        $actions->add(Crud::PAGE_INDEX, Action::new('rozetkaXmlForP', 'Rozetka feed for P *.xml')
            ->linkToCrudAction('rozetkaForPXmlAction')
            ->createAsGlobalAction());

        return parent::configureActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        $category = null;
        $editingEntityInstance = null;

        if ($this->getContext()?->getEntity()?->getInstance()) {
            $editingEntityInstance = $this->getContext()->getEntity()->getInstance();
            $category = $editingEntityInstance->getProduct()->getCategory();
        }

        yield IdField::new('product.id', 'Id')->hideOnForm();
        yield TextField::new('title', 'Назва')->setColumns(6);
        yield NumberField::new('stock_quantity', 'Кількість')
            ->setThousandsSeparator(' ')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextField::new('series', 'Серія')->setColumns(6);
        yield NumberField::new('price', 'Ціна')
            ->setThousandsSeparator(' ')
            ->setColumns(6);
        yield NumberField::new('crossedOutPrice', 'Перекреслена ціна (грн)')
            ->setThousandsSeparator(' ')
            ->hideOnIndex()
            ->setColumns(6);
        yield NumberField::new('promoPrice', 'Промо-ціна (грн)')
            ->setThousandsSeparator(' ')
            ->setNumDecimals(0)
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Працює лише якщо увімкнено перемикач')
            ->setColumns(3);
        yield BooleanField::new('promoPriceActive', 'Активувати промо-ціну')
            ->renderAsSwitch(true)
            ->setColumns(3)
            ->hideOnIndex()
            ->setFormTypeOption('row_attr', ['class' => 'mt-4']);
        if ($editingEntityInstance && $editingEntityInstance->getReady() === false) {
            yield AssociationField::new('rozetkaProduct', 'Товар, з якого копіювати характеристики')
                ->setFormTypeOption('query_builder', function (EntityRepository $entityRepository) use ($category) {
                    return $entityRepository->createQueryBuilder('rp')
                        ->leftJoin('rp.product', 'p')
                        ->where('p.category=:category')
                        ->setParameter('category', $category)
                        ->andWhere('rp.ready = :ready')
                        ->setParameter('ready', true);
                })
                ->setColumns(6)->hideOnIndex();
        }
        yield BooleanField::new('ready', 'Готовий')->setColumns(7);
        yield BooleanField::new('activeForA', 'Активний для A')
            ->setCustomOption('dependent', true)
            ->setDisabled(!($pageName === Crud::PAGE_INDEX) && $this->isDisabled())
            ->setColumns(7);
        yield BooleanField::new('activeForP', 'Активний для P')
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

    public function rozetkaForAXmlAction(AdminContext $adminContext): RedirectResponse
    {
        $this->generateRozetkaXmlService->execute();

        $this->addFlash('success', 'Document is generated <a href="/rozetka_for_a/products.xml" target="_blank">here</a>');

        return $this->redirect($this->adminUrlGenerator->setController(RozetkaProductCrudController::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function rozetkaForPXmlAction(AdminContext $adminContext): RedirectResponse
    {
        $this->generateRozetkaXmlService->execute('active_for_p');

        $this->addFlash('success', 'Document is generated <a href="/rozetka_for_p/products.xml" target="_blank">here</a>');

        return $this->redirect($this->adminUrlGenerator->setController(RozetkaProductCrudController::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var RozetkaProduct $entityInstance */
        $request = $this->requestStack->getCurrentRequest();

        if ($request->request->has('ea_custom_action')) {
            $parentRozetkaProduct = $request->request->get('RozetkaProduct');
            $rozetkaProductId = $parentRozetkaProduct['rozetkaProduct'];

            $rozetkaProduct = $this->rozetkaProductRepository->findOneBy(['id' => $rozetkaProductId]);

            $entityInstance->setSeries($rozetkaProduct->getSeries());
            $entityInstance->setDescription($rozetkaProduct->getDescription());

            if ($rozetkaProduct->getValues()) {
                if ($entityInstance->getValues()) {
                    foreach ($entityInstance->getValues() as $value) {
                        $entityInstance->removeValue($value);
                    }
                }
                foreach($rozetkaProduct->getValues() as $value) {
                    $productValue = new ProductRozetkaCharacteristicValue();
                    $productValue->setRozetkaProduct($entityInstance);
                    $productValue->setCharacteristic($value->getCharacteristic());
                    $productValue->setValue($value->getValue());
                    $productValue->setStringValue($value->getStringValue());
                    if ($value->getValues()) {
                        foreach($value->getValues() as $itemValue) {
                            $productValue->addValue($itemValue);
                        }
                    }

                    $productValue->setStringValue($value->getStringValue());
                    $entityInstance->addValue($productValue);
                }
            }
        }

        $entityManager->persist($entityInstance);
        $entityManager->flush();
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
