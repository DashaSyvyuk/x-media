<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductCharacteristic;
use App\Entity\ProductFilterAttribute;
use App\Entity\ProductImage;
use App\Form\CommentType;
use App\Form\ProductCharacteristicType;
use App\Form\ProductFilterAttributeType;
use App\Form\ProductImageType;
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

class ProductCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
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
            ->setSearchFields(['orderNumber', 'surname', 'phone', 'email'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10)
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Загальна інформація');
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Назва')->setColumns(6);
        yield ChoiceField::new('status', 'Статус')->setChoices([
            Product::STATUS_ACTIVE => Product::STATUS_ACTIVE,
            Product::STATUS_BLOCKED => Product::STATUS_BLOCKED,
        ])->setColumns(6)->hideOnIndex();
        yield AssociationField::new('category', 'Категорія')->setColumns(6)->hideOnIndex();
        yield TextField::new('note', 'Нотатки')->setColumns(6)->hideOnIndex();
        yield TextField::new('productCode', 'Код товару')->setColumns(6)->hideOnIndex();
        yield TextField::new('metaKeyword', 'Мета тег (Ключові слова)')->setColumns(6)->hideOnIndex();
        yield TextField::new('metaDescription', 'Мета тег (Опис)')->setColumns(6)->hideOnIndex();

        yield FormField::addPanel('Ціни');
        yield AssociationField::new('currency', 'Валюта')->hideOnIndex()->setColumns(6);
        yield NumberField::new('priceWithVAT', 'Ціна з ПДВ (zl)')->hideOnIndex()->setColumns(6);
        yield NumberField::new('priceWithoutVAT', 'Ціна без ПДВ (zl)')->setFormTypeOptions([
            'disabled' => true
        ])->hideOnIndex()->setColumns(6);
        yield NumberField::new('purchasePriceUAH', 'Ціна (грн)')->setFormTypeOptions([
            'disabled' => true
        ])->hideOnIndex()->setColumns(6);
        yield NumberField::new('deliveryCost', 'Витрати на доставку (грн)')->hideOnIndex()->setColumns(6);
        yield NumberField::new('totalPrice', 'Загальна вартість товару (грн)')->setFormTypeOptions([
            'disabled' => true,
        ])->hideOnIndex()->setColumns(6);
        yield NumberField::new('price', 'Ціна (грн)')->setColumns(6);
        yield NumberField::new('marge', 'Marge (грн)')->setFormTypeOptions([
            'disabled' => true,
        ])->hideOnIndex()->setColumns(6);
        yield NumberField::new('margePercentage', 'Marge(%)')->setFormTypeOptions([
            'disabled' => true,
        ])->hideOnIndex()->setColumns(6);

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

        yield FormField::addPanel('Відгуки');
        yield CollectionField::new('comments')
            ->setColumns(12)
            ->setEntryType(CommentType::class)
            ->renderExpanded(false)
            ->onlyOnForms();
    }

    public function edit(AdminContext $context)
    {
        if ($context->getRequest()->query->has('duplicate')) {
            $entity = $context->getEntity()->getInstance();
            /** @var Product $cloned */
            $cloned = clone $entity;
            $cloned->setCreatedAt(new \DateTime('now'));

            if ($entity->getCharacteristics()) {
                foreach($entity->getCharacteristics() as $characteristic) {
                    $productCharacteristic = new ProductCharacteristic();
                    $productCharacteristic->setTitle($characteristic->getTitle());
                    $productCharacteristic->setValue($characteristic->getValue());
                    $productCharacteristic->setPosition($characteristic->getPosition());

                    $cloned->addCharacteristic($productCharacteristic);
                }
            }

            if ($entity->getFilterAttributes()) {
                foreach ($entity->getFilterAttributes() as $filterAttribute) {
                    $productFilterAttribute = new ProductFilterAttribute();
                    $productFilterAttribute->setFilter($filterAttribute->getFilter());
                    $productFilterAttribute->setFilterAttribute($filterAttribute->getFilterAttribute());

                    $cloned->addFilterAttribute($productFilterAttribute);
                }
            }

            if ($entity->getImages()) {
                foreach ($entity->getImages() as $image) {
                    $productImage = new ProductImage();
                    $productImage->setImageUrl($image->getImageUrl());
                    $productImage->setPosition($image->getPosition());

                    $cloned->addImage($productImage);
                }
            }
            $context->getEntity()->setInstance($cloned);
        }

        return parent::edit($context);
    }

    public function configureActions(Actions $actions): Actions
    {
        $duplicate = Action::new('duplicate', false)
            ->setLabel('Copy')
            ->linkToUrl(
                fn(Product $entity) => $this->adminUrlGenerator
                    ->setAction(Action::EDIT)
                    ->setEntityId($entity->getId())
                    ->set('duplicate', '1')
                    ->generateUrl()
            );

        $actions->add(Crud::PAGE_INDEX, $duplicate);

        return parent::configureActions($actions);
    }
}
