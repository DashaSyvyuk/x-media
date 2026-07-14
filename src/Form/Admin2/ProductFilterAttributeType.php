<?php

namespace App\Form\Admin2;

use App\Entity\Category;
use App\Entity\Filter;
use App\Entity\FilterAttribute;
use App\Entity\ProductFilterAttribute;
use App\Repository\FilterRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilterAttributeType extends AbstractType
{
    public function __construct(private readonly FilterRepository $filterRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Category|null $category */
        $category = $options['category'];
        $filters  = $category ? $this->filterRepository->findBy(['category' => $category]) : [];

        $builder->add('filter', EntityType::class, [
            'class'         => Filter::class,
            'placeholder'   => 'Оберіть фільтр',
            'choices'       => $filters,
            'label'         => 'Фільтр',
            'required'      => true,
            'attr'          => ['class' => 'filter-select'],
        ]);

        $formModifier = function (FormInterface $form, ?Filter $filter = null): void {
            $attributes = $filter?->getFilterAttributes() ?? [];

            $form->add('filterAttribute', EntityType::class, [
                'class'        => FilterAttribute::class,
                'placeholder'  => 'Оберіть параметр',
                'choices'      => $attributes,
                'label'        => 'Параметр',
                'required'     => true,
                'attr'         => ['class' => 'filter-attribute-select'],
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier): void {
                /** @var ProductFilterAttribute|null $data */
                $data = $event->getData();
                $formModifier($event->getForm(), $data?->getFilter());
            }
        );

        $builder->get('filter')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier): void {
                $filter = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $filter instanceof Filter ? $filter : null);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductFilterAttribute::class,
            'category'   => null,
        ]);

        $resolver->setAllowedTypes('category', ['null', Category::class]);
    }
}
