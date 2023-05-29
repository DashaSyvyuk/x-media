<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\CommentType;
use App\Form\ProductCharacteristicType;
use App\Form\ProductFilterAttributeType;
use App\Form\ProductImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Товари')
            ->setEntityLabelInPlural('Товар')
           // ->setSearchFields(['orderNumber', 'surname', 'phone', 'email'])
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
                    'config'=>[
                        'toolbar' => 'full',
                        'extraPlugins' => 'templates',
                        'rows' => '40',

                    ],
                    'attr' => ['rows' => '40'] ,

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
            ->renderExpanded()
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
            ->renderExpanded()
            ->onlyOnForms();
    }
}
