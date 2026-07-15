<?php

namespace App\Form\Admin2;

use App\Entity\DeliveryType;
use App\Entity\Order;
use App\Entity\PaymentType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<string, string> $statusChoices */
        $statusChoices = $options['status_choices'];
        $statusFormChoices = [];
        foreach ($statusChoices as $code => $label) {
            $statusFormChoices[$label] = $code;
        }

        $builder
            ->add('orderNumber', TextType::class, [
                'label'    => 'Номер замовлення',
                'disabled' => $options['is_edit'],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Статус',
                'choices' => $statusFormChoices,
            ])
            ->add('ttn', TextType::class, [
                'label'    => 'ТТН',
                'required' => false,
            ])
            ->add('name', TextType::class, ['label' => 'Ім\'я'])
            ->add('surname', TextType::class, ['label' => 'Прізвище'])
            ->add('phone', TextType::class, ['label' => 'Телефон'])
            ->add('email', TextType::class, [
                'label'    => 'Email',
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label'    => 'Коментар',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label'    => 'Адреса',
                'required' => false,
            ])
            ->add('paytype', EntityType::class, [
                'class'        => PaymentType::class,
                'label'        => 'Спосіб оплати',
                'choice_label' => 'title',
                'required'     => false,
            ])
            ->add('deltype', EntityType::class, [
                'class'        => DeliveryType::class,
                'label'        => 'Спосіб доставки',
                'choice_label' => 'title',
                'required'     => false,
            ])
            ->add('sendNotification', SwitchType::class, [
                'label'     => 'Відправляти сповіщення',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('paymentStatus', SwitchType::class, [
                'label'     => 'Оплачено',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('labels', ChoiceType::class, [
                'label'    => 'Мітки',
                'choices'  => Order::LABELS,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('items', CollectionType::class, [
                'label'        => 'Товари',
                'entry_type'   => OrderItemEntryType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('source', HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => Order::class,
            'status_choices'  => Order::STATUSES,
            'is_edit'         => false,
        ]);

        $resolver->setAllowedTypes('status_choices', 'array');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
