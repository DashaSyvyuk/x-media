<?php

namespace App\Form\Admin2;

use App\Entity\Category;
use App\Entity\Filter;
use App\Entity\FilterAttribute;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Назва параметру'])
            ->add('category', EntityType::class, [
                'class'         => Category::class,
                'label'         => 'Категорія',
                'placeholder'   => 'Оберіть категорію',
                'query_builder' => fn (EntityRepository $repository) => $repository->createQueryBuilder('c')
                    ->orderBy('c.title', 'ASC'),
            ])
            ->add('priority', IntegerType::class, ['label' => 'Пріоритет'])
            ->add('openedCount', IntegerType::class, ['label' => 'Кількість відкритих параметрів'])
            ->add('isOpened', SwitchType::class, [
                'label'     => 'Закритий',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('attributes', CollectionType::class, [
                'entry_type'    => FilterAttributeEntryType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'label'         => false,
                'entry_options' => ['label' => false],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Filter::class,
        ]);
    }
}
