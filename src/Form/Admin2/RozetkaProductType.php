<?php

namespace App\Form\Admin2;

use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaProduct;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
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
                'entry_options'  => [
                    'category' => $category,
                    'required' => false,
                ],
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference'   => false,
                'label'          => false,
                'prototype_name' => '__name__',
                'required'       => false,
            ])
        ;

        if (! $rozetkaProduct->getReady()) {
            $builder->add('copySourceProduct', ProductPickerType::class, [
                'label'         => 'ID товару, з якого копіювати характеристики',
                'required'      => false,
                'mapped'        => false,
                'error_bubbling' => false,
            ]);
        }

        $normalizePrices = static function (?RozetkaProduct $data): void {
            if ($data === null) {
                return;
            }

            if ($data->getPromoPrice() !== null && $data->getPromoPrice() < 1) {
                $data->setPromoPrice(null);
            }

            if ($data->getCrossedOutPrice() !== null && $data->getCrossedOutPrice() < 1) {
                $data->setCrossedOutPrice(null);
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($normalizePrices): void {
            /** @var RozetkaProduct|null $data */
            $data = $event->getData();
            $normalizePrices($data);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $submitted = $event->getData();
            if (! is_array($submitted) || ! isset($submitted['values']) || ! is_array($submitted['values'])) {
                return;
            }

            $submitted['values'] = array_values(array_filter(
                $submitted['values'],
                static function ($row): bool {
                    if (! is_array($row)) {
                        return false;
                    }

                    $characteristic = $row['characteristic'] ?? null;

                    return $characteristic !== null && $characteristic !== '';
                },
            ));

            $event->setData($submitted);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($normalizePrices): void {
            /** @var RozetkaProduct|null $data */
            $data = $event->getData();
            $normalizePrices($data);

            if ($data === null) {
                return;
            }

            foreach ($data->getValues()->toArray() as $value) {
                if (! $value instanceof ProductRozetkaCharacteristicValue) {
                    continue;
                }
                if ($value->getCharacteristic() === null) {
                    $data->removeValue($value);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => RozetkaProduct::class,
            'disable_feed_flags' => false,
        ]);
    }
}
