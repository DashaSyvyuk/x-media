<?php

namespace App\Form;

use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaCharacteristics;
use App\Entity\RozetkaCharacteristicsValue;
use App\Repository\RozetkaCharacteristicsRepository;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
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
                'label' => 'Значення',
                'attr' => [
                    'class' => 'rozetka-characteristics-values'
                ],
            ]
        ],
        'Decimal' => [
            'type' => NumberType::class,
            'attributes' => [
                'label' => 'Значення',
                'scale' => 2,
                'attr' => [
                    'class' => 'rozetka-characteristics-values'
                ],
            ]
        ],
        'TextInput' => [
            'type' => TextType::class,
            'attributes' => [
                'label' => 'Значення',
                'attr' => [
                    'class' => 'rozetka-characteristics-values'
                ],
            ]
        ],
        'TextArea' => [
            'type' => TextareaType::class,
            'attributes' => [
                'label' => 'Значення',
                'attr' => [
                    'class' => 'rozetka-characteristics-values'
                ],
            ]
        ]
    ];

    public function __construct(
        private readonly AdminContextProvider $adminContextProvider,
        private readonly RozetkaCharacteristicsRepository $rozetkaCharacteristicsRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $category = $this->adminContextProvider->getContext()->getEntity()->getInstance()->getProduct()->getCategory();
        $characteristics = $this->rozetkaCharacteristicsRepository->getCharacteristicsForCategory($category);

        $builder
            ->add('characteristic', EntityType::class, [
                'class' => RozetkaCharacteristics::class,
                'placeholder' => 'Оберіть параметр',
                'choices' => $characteristics,
                'label' => 'Параметр',
                'required' => true,
                'attr' => [
                    'class' => 'characteristic'
                ]
            ])
        ;

        $formModifier = function (FormInterface $form, RozetkaCharacteristics $characteristics = null) {
            $attributes = null === $characteristics ?
                [] :
                $characteristics->getValues()->filter(function (RozetkaCharacteristicsValue $value) {
                    return $value->getActive() === true;
                });
            $type = $characteristics?->getType();

            if (in_array($type, self::NEED_TEXT_FIELD)) {
                $form->add(
                    'stringValue',
                    self::TEXT_FIELD_TYPES[$type]['type'],
                    self::TEXT_FIELD_TYPES[$type]['attributes']
                );
            } else {
                $form->add(in_array($type, self::ONE_VALUE_LIST) ? 'value' : 'values', EntityType::class, [
                    'class' => RozetkaCharacteristicsValue::class,
                    'placeholder' => 'Оберіть параметр',
                    'choices' => $attributes,
                    'label' => 'Значення',
                    'required' => true,
                    'attr' => [
                        'class' => 'rozetka-characteristics-values'
                    ],
                    'multiple' => !in_array($type, self::ONE_VALUE_LIST),
                ]);
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();

                $formModifier($event->getForm(), $data?->getCharacteristic());
            }
        );

        $builder->get('characteristic')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $characteristic = $event->getForm()->getData();

                // since we've added the listener to the child, we'll have to pass on
                // the parent to the callback function!
                $formModifier($event->getForm()->getParent(), $characteristic);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => ProductRozetkaCharacteristicValue::class,
        ]);
    }
}
