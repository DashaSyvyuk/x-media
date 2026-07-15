<?php

namespace App\Form\Admin2;

use App\Entity\Supplier;
use App\Entity\VendorOrder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VendorOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $statusChoices = [];
        foreach (VendorOrder::STATUSES as $code => $label) {
            $statusChoices[$label] = $code;
        }

        $builder
            ->add('supplier', EntityType::class, [
                'class'        => Supplier::class,
                'label'        => 'Постачальник',
                'choice_label' => 'title',
                'required'     => true,
            ])
            ->add('supplierOrderNumber', TextType::class, [
                'label' => 'Номер замовлення постачальника',
            ])
            ->add('items', CollectionType::class, [
                'label'        => 'Товари',
                'entry_type'   => VendorOrderItemEntryType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Нотатки',
                'required' => false,
                'attr'     => ['rows' => 3],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Статус',
                'choices' => $statusChoices,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VendorOrder::class,
        ]);
    }
}
