<?php

namespace App\Form\Admin2;

use App\Entity\InStock;
use App\Entity\Warehouse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', ProductPickerType::class, [
                'label'    => 'Товар',
                'required' => true,
            ])
            ->add('warehouse', EntityType::class, [
                'class'        => Warehouse::class,
                'label'        => 'Склад',
                'choice_label' => static function (Warehouse $warehouse): string {
                    $city  = trim($warehouse->getCity());
                    $title = trim($warehouse->getTitle());

                    if ($city !== '' && $title !== '') {
                        return $city . ' (' . $title . ')';
                    }

                    return $title !== '' ? $title : $city;
                },
                'required'     => true,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Кількість',
                'attr'  => ['min' => 0],
            ])
            ->add('purchasePrice', IntegerType::class, [
                'label'    => 'Закупівельна ціна',
                'required' => false,
                'attr'     => ['min' => 0],
            ])
            ->add('note', TextareaType::class, [
                'label'    => 'Нотатка',
                'required' => false,
                'attr'     => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InStock::class,
        ]);
    }
}
