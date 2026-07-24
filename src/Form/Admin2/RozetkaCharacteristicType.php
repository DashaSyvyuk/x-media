<?php

namespace App\Form\Admin2;

use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaCharacteristics;
use App\Entity\RozetkaCharacteristicsValue;
use App\Repository\RozetkaCharacteristicsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RozetkaCharacteristicType extends AbstractType
{
    public const ONE_VALUE_LIST = ['ComboBox'];
    public const NEED_TEXT_FIELD = ['Integer', 'Decimal', 'TextInput', 'TextArea'];

    public const TEXT_FIELD_TYPES = [
        'Integer' => [
            'type' => IntegerType::class,
            'attributes' => [
                'label'    => 'Значення',
                'required' => false,
                'attr'     => ['class' => 'rozetka-characteristics-values'],
            ],
        ],
        'Decimal' => [
            'type' => NumberType::class,
            'attributes' => [
                'label'    => 'Значення',
                'required' => false,
                'scale'    => 2,
                'attr'     => ['class' => 'rozetka-characteristics-values'],
            ],
        ],
        'TextInput' => [
            'type' => TextType::class,
            'attributes' => [
                'label'    => 'Значення',
                'required' => false,
                'attr'     => ['class' => 'rozetka-characteristics-values'],
            ],
        ],
        'TextArea' => [
            'type' => TextareaType::class,
            'attributes' => [
                'label'    => 'Значення',
                'required' => false,
                'attr'     => ['class' => 'rozetka-characteristics-values'],
            ],
        ],
    ];

    public function __construct(
        private readonly RozetkaCharacteristicsRepository $rozetkaCharacteristicsRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $category = $options['category'];
        $characteristics = $category
            ? $this->rozetkaCharacteristicsRepository->getCharacteristicsForCategory($category)
            : [];

        $builder->add('characteristic', EntityType::class, [
            'class'        => RozetkaCharacteristics::class,
            'placeholder'  => 'Оберіть параметр',
            'choices'      => $characteristics,
            'label'        => 'Параметр',
            'required'     => false,
            'attr'         => ['class' => 'characteristic'],
        ]);

        $formModifier = function (FormInterface $form, ?RozetkaCharacteristics $characteristic = null): void {
            $attributes = $characteristic === null
                ? []
                : $characteristic->getValues()->filter(
                    fn (RozetkaCharacteristicsValue $value): bool => $value->getActive() === true,
                );
            $type = $characteristic?->getType();

            if (in_array($type, self::NEED_TEXT_FIELD, true)) {
                $form->add(
                    'stringValue',
                    self::TEXT_FIELD_TYPES[$type]['type'],
                    self::TEXT_FIELD_TYPES[$type]['attributes'],
                );
            } elseif ($type !== null) {
                $form->add(in_array($type, self::ONE_VALUE_LIST, true) ? 'value' : 'values', EntityType::class, [
                    'class'        => RozetkaCharacteristicsValue::class,
                    'placeholder'  => 'Оберіть значення',
                    'choices'      => $attributes,
                    'label'        => 'Значення',
                    'required'     => false,
                    'attr'         => ['class' => 'rozetka-characteristics-values'],
                    'multiple'     => ! in_array($type, self::ONE_VALUE_LIST, true),
                ]);
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event) use ($formModifier): void {
                /** @var ProductRozetkaCharacteristicValue|null $data */
                $data = $event->getData();
                $formModifier($event->getForm(), $data?->getCharacteristic());
            },
        );

        $builder->get('characteristic')->addEventListener(
            FormEvents::POST_SUBMIT,
            static function (FormEvent $event) use ($formModifier): void {
                $characteristic = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $characteristic);
            },
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductRozetkaCharacteristicValue::class,
            'category'   => null,
        ]);

        $resolver->setAllowedTypes('category', ['null', 'object']);
    }
}
