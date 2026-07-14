<?php

namespace App\Form\Admin2;

use App\Entity\Category;
use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaProduct;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RozetkaProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var RozetkaProduct $rozetkaProduct */
        $rozetkaProduct = $options['data'];
        $category       = $rozetkaProduct->getProduct()->getCategory();

        $builder
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('stockQuantity', IntegerType::class, ['label' => 'Кількість'])
            ->add('series', TextType::class, [
                'label'    => 'Серія',
                'required' => false,
            ])
            ->add('price', IntegerType::class, ['label' => 'Ціна'])
            ->add('crossedOutPrice', IntegerType::class, [
                'label'    => 'Перекреслена ціна (грн)',
                'required' => false,
            ])
            ->add('promoPrice', IntegerType::class, [
                'label'    => 'Промо-ціна (грн)',
                'required' => false,
                'help'     => 'Працює лише якщо увімкнено перемикач',
            ])
            ->add('promoPriceActive', SwitchType::class, [
                'label'     => 'Активувати промо-ціну',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('ready', SwitchType::class, [
                'label'     => 'Готовий',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('activeForA', SwitchType::class, [
                'label'     => 'Активний для A',
                'on_value'  => true,
                'off_value' => false,
                'disabled'  => $options['disable_feed_flags'],
            ])
            ->add('activeForP', SwitchType::class, [
                'label'     => 'Активний для P',
                'on_value'  => true,
                'off_value' => false,
                'disabled'  => $options['disable_feed_flags'],
            ])
            ->add('description', CKEditorType::class, [
                'label' => 'Опис укр',
                'config' => [
                    'toolbar'      => 'full',
                    'extraPlugins' => 'templates',
                    'rows'         => 40,
                ],
                'attr' => ['rows' => 40],
            ])
            ->add('values', CollectionType::class, [
                'entry_type'     => RozetkaCharacteristicType::class,
                'entry_options'  => ['category' => $category],
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference'   => false,
                'label'          => false,
                'prototype_name' => '__name__',
            ])
        ;

        if (! $rozetkaProduct->getReady()) {
            $builder->add('rozetkaProduct', EntityType::class, [
                'class'         => RozetkaProduct::class,
                'label'         => 'Товар, з якого копіювати характеристики',
                'required'      => false,
                'placeholder'   => 'Оберіть готовий товар категорії',
                'choice_label'  => static fn (RozetkaProduct $item): string => (string) $item,
                'query_builder' => static function ($repository) use ($category) {
                    $qb = $repository->createQueryBuilder('rp')
                        ->leftJoin('rp.product', 'p')
                        ->andWhere('rp.ready = :ready')
                        ->setParameter('ready', true)
                        ->orderBy('rp.title', 'ASC');

                    if ($category instanceof Category) {
                        $qb->andWhere('p.category = :category')
                            ->setParameter('category', $category);
                    }

                    return $qb;
                },
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            /** @var RozetkaProduct|null $data */
            $data = $event->getData();
            if ($data === null) {
                return;
            }

            $values = $data->getValues();
            if ($values->isEmpty()) {
                $data->addValue(new ProductRozetkaCharacteristicValue());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'          => RozetkaProduct::class,
            'disable_feed_flags'  => false,
        ]);
    }
}
