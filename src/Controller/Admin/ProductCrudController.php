<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductCharacteristic;
use App\Entity\ProductFilterAttribute;
use App\Form\ProductCharacteristicType;
use App\Form\ProductFilterAttributeType;
use App\Form\ProductImageType;
use App\Form\ProductPromotionType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\GenerateEkatalogXmlService;
use App\Service\GenerateHotlineXmlService;
use App\Service\GeneratePromXmlService;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')")]
class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator          $adminUrlGenerator,
        private readonly ProductRepository          $productRepository,
        private readonly GenerateHotlineXmlService  $generateHotlineXmlService,
        private readonly GeneratePromXmlService     $generatePromXmlService,
        private readonly CategoryRepository         $categoryRepository,
        private readonly GenerateEkatalogXmlService $generateEkatalogXmlService,
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
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
            ->setSearchFields(['id', 'title', 'productCode'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->add(Crud::PAGE_INDEX, Action::new('Copy', 'Copy')
            ->linkToCrudAction('cloneAction'));

        $actions->add(Crud::PAGE_INDEX, Action::new('hotlineXml', 'Hotline feed *.xml')
            ->linkToCrudAction('hotlineXmlAction')
            ->createAsGlobalAction());

        $actions->add(Crud::PAGE_INDEX, Action::new('promXml', 'Prom feed *.xml')
            ->linkToCrudAction('promXmlAction')
            ->createAsGlobalAction());

        $actions->add(Crud::PAGE_INDEX, Action::new('ekatalogXml', 'E-Katalog feed *.xml')
            ->linkToCrudAction('ekatalogXmlAction')
            ->createAsGlobalAction());

        return parent::configureActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Загальна інформація');
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Назва')->setColumns(6);
        yield ChoiceField::new('status', 'Статус')->setChoices([
            Product::STATUS_ACTIVE => Product::STATUS_ACTIVE,
            Product::STATUS_BLOCKED => Product::STATUS_BLOCKED,
        ])
            ->renderAsBadges([
                Product::STATUS_BLOCKED => 'danger',
                Product::STATUS_ACTIVE => 'success',
            ])
            ->setColumns(6);
        yield ChoiceField::new('availability', 'Наявність')
            ->setChoices(Product::AVAILABILITIES)->setColumns(6)->hideOnIndex();
        yield AssociationField::new('category', 'Категорія')
            ->setFormTypeOption('query_builder', function (EntityRepository $entityRepository) {
                return $entityRepository->createQueryBuilder('c')
                    ->where('c.id IN (:ids)')
                    ->setParameter('ids', $this->categoryRepository->getCategoriesIdsWithoutChildren());
            })->setColumns(6)->hideOnIndex();
        yield TextField::new('note', 'Нотатки')->setColumns(6)->hideOnIndex();
        yield TextField::new('productCode', 'Код товару')->setColumns(6);
        yield TextField::new('productCode2', 'Код товару 2')->setColumns(6)->hideOnIndex();
        yield TextField::new('olx', 'Olx')->setColumns(6)->hideOnIndex();

        yield FormField::addPanel('Ціни');
        yield NumberField::new('price', 'Ціна (грн)')
            ->setThousandsSeparator(' ')
            ->setColumns(6);
        yield NumberField::new('crossedOutPrice', 'Перекреслена ціна (грн)')
            ->setThousandsSeparator(' ')
            ->hideOnIndex()
            ->setColumns(6);

        yield FormField::addPanel('Опис');
        yield TextareaField::new('description', 'Опис')
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

        yield FormField::addPanel('Зображення');
        yield CollectionField::new('images')
            ->setColumns(12)
            ->setEntryType(ProductImageType::class)
            ->renderExpanded()
            ->onlyOnForms();

        yield FormField::addPanel('Характеристики');
        yield CollectionField::new('characteristics')
            ->setColumns(12)
            ->setEntryType(ProductCharacteristicType::class)
            ->renderExpanded(false)
            ->onlyOnForms();

        yield FormField::addPanel('Фільтри');
        yield CollectionField::new('filterAttributes')
            ->setColumns(12)
            ->setEntryType(ProductFilterAttributeType::class)
            ->renderExpanded()
            ->onlyOnForms();

        yield FormField::addPanel('Акції');
        yield CollectionField::new('promotionProducts')
            ->setColumns(12)
            ->setEntryType(ProductPromotionType::class)
            ->renderExpanded(false)
            ->onlyOnForms();
    }

    public function cloneAction(AdminContext $context): RedirectResponse
    {
        $id = $context->getRequest()->query->get('entityId');
        $entity = $this->productRepository->find($id);

        $clone = clone $entity;
        $clone->setCreatedAt(new \DateTime('now'));
        $clone->setTitle(sprintf('%s (Copy)', $entity->getTitle()));

        if ($entity->getCharacteristics()) {
            foreach($entity->getCharacteristics() as $characteristic) {
                $productCharacteristic = new ProductCharacteristic();
                $productCharacteristic->setTitle($characteristic->getTitle());
                $productCharacteristic->setValue($characteristic->getValue());
                $productCharacteristic->setPosition($characteristic->getPosition());

                $clone->addCharacteristic($productCharacteristic);
            }
        }

        if ($entity->getFilterAttributes()) {
            foreach ($entity->getFilterAttributes() as $filterAttribute) {
                $productFilterAttribute = new ProductFilterAttribute();
                $productFilterAttribute->setFilter($filterAttribute->getFilter());
                $productFilterAttribute->setFilterAttribute($filterAttribute->getFilterAttribute());

                $clone->addFilterAttribute($productFilterAttribute);
            }
        }

        $this->persistEntity($this->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $clone);
        $this->addFlash('success', 'Product duplicated');

        return $this->redirect($this->adminUrlGenerator->setController(ProductCrudController::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function hotlineXmlAction(AdminContext $adminContext): RedirectResponse
    {
        $this->generateHotlineXmlService->execute();

        $this->addFlash('success', 'Document is generated <a href="/hotline/products.xml" target="_blank">here</a>');

        return $this->redirect($this->adminUrlGenerator->setController(ProductCrudController::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function promXmlAction(AdminContext $adminContext): RedirectResponse
    {
        $this->generatePromXmlService->execute();

        $this->addFlash('success', 'Document is generated <a href="/prom/products.xml" target="_blank">here</a>');

        return $this->redirect($this->adminUrlGenerator->setController(ProductCrudController::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function ekatalogXmlAction(AdminContext $adminContext): RedirectResponse
    {
        $this->generateEkatalogXmlService->execute();

        $this->addFlash('success', 'Document is generated <a href="/e-katalog/products.xml" target="_blank">here</a>');

        return $this->redirect($this->adminUrlGenerator->setController(ProductCrudController::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $search = trim($searchDto->getQuery());
        if ($search !== '') {
            // Розбиваємо на слова
            $words = preg_split('/\s+/', $search);

            // Очистимо попередні умови пошуку
            $alias = $qb->getRootAliases()[0];
            $orX = $qb->expr()->orX();

            foreach (['title', 'productCode', 'id'] as $field) {
                $andX = $qb->expr()->andX();
                foreach ($words as $k => $word) {
                    $paramName = ":{$field}_word{$k}";
                    $andX->add($qb->expr()->like("LOWER($alias.$field)", "LOWER($paramName)"));
                    $qb->setParameter($paramName, '%' . $word . '%');
                }
                $orX->add($andX);
            }

            $qb->andWhere($orX);
        }

        return $qb;
    }
}
