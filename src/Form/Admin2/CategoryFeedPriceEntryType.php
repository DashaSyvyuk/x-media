<?php

namespace App\Form\Admin2;

use App\Entity\CategoryFeedPrice;
use App\Entity\Feed;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFeedPriceEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('feed', EntityType::class, [
                'class'        => Feed::class,
                'label'        => 'Feed',
                'choice_label' => 'type',
            ])
            ->add('ourPercent', IntegerType::class, ['label' => 'Наш %'])
            ->add('fee', IntegerType::class, ['label' => 'Комісія'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategoryFeedPrice::class,
        ]);
    }
}
