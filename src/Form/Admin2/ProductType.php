<?php

namespace App\Form\Admin2;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Promotion;
use App\Entity\PromotionProduct;
use App\Form\ProductCharacteristicType;
use App\Form\ProductImageType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categoryIds = $this->categoryRepository->getCategoriesIdsWithoutChildren();

        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('status', SwitchType::class, [
                'label'     => 'Активний',
                'on_value'  => Product::STATUS_ACTIVE,
                'off_value' => Product::STATUS_BLOCKED,
            ])
            ->add('availability', ChoiceType::class, [
                'label'   => 'Наявність',
                'choices' => Product::AVAILABILITIES,
            ])
            ->add('category', EntityType::class, [
                'class'         => Category::class,
                'label'         => 'Категорія',
                'placeholder'   => 'Оберіть категорію',
                'query_builder' => function (EntityRepository $repository) use ($categoryIds) {
                    $qb = $repository->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($categoryIds !== []) {
                        $qb->andWhere('c.id IN (:ids)')->setParameter('ids', $categoryIds);
                    }

                    return $qb;
                },
            ])
            ->add('note', TextType::class, ['label' => 'Нотатки', 'required' => false])
            ->add('productCode', TextType::class, ['label' => 'Код товару'])
            ->add('productCode2', TextType::class, ['label' => 'Код товару 2', 'required' => false])
            ->add('olx', TextType::class, ['label' => 'Olx', 'required' => false])
            ->add('xkomUrl', TextType::class, [
                'label'    => 'x-kom',
                'required' => false,
                'attr'     => ['placeholder' => 'https://www.x-kom.pl/...'],
            ])
            ->add('price', IntegerType::class, ['label' => 'Ціна (грн)'])
            ->add('crossedOutPrice', IntegerType::class, [
                'label'    => 'Перекреслена ціна (грн)',
                'required' => false,
            ])
            ->add('description', CKEditorType::class, ['label' => 'Опис'])
            ->add('images', CollectionType::class, [
                'entry_type'   => ProductImageType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label'        => false,
                'entry_options'=> ['label' => false],
            ])
            ->add('characteristics', CollectionType::class, [
                'entry_type'   => ProductCharacteristicType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label'        => false,
                'entry_options'=> ['label' => false],
            ])
            ->add('promotionProducts', CollectionType::class, [
                'entry_type'   => Admin2PromotionProductType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label'        => false,
                'entry_options'=> ['label' => false],
            ])
        ;

        $filterModifier = function (FormInterface $form, ?Category $category): void {
            if ($form->has('filterAttributes')) {
                $form->remove('filterAttributes');
            }

            $form->add('filterAttributes', CollectionType::class, [
                'entry_type'    => ProductFilterAttributeType::class,
                'entry_options' => [
                    'label'    => false,
                    'category' => $category,
                ],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'label'         => false,
            ]);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($filterModifier): void {
            /** @var Product|null $product */
            $product = $event->getData();
            $filterModifier($event->getForm(), $product?->getCategory());
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($filterModifier): void {
            $data = $event->getData();
            $category = null;

            if (! empty($data['category'])) {
                $category = $this->categoryRepository->find((int) $data['category']);
            }

            $filterModifier($event->getForm(), $category instanceof Category ? $category : null);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
