<?php

namespace App\Form;

use App\Entity\Filter;
use App\Entity\FilterAttribute;
use App\Entity\ProductFilterAttribute;
use App\Repository\FilterRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilterAttributeType extends AbstractType
{
    public function __construct(
        private readonly AdminContextProvider $adminContextProvider,
        private readonly FilterRepository $filterRepository
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $category = $this->adminContextProvider->getContext()->getEntity()->getInstance()->getCategory();
        $filters = $this->filterRepository->findBy(['category' => $category]);

        $builder
            ->add('filter', EntityType::class, [
                'class' => Filter::class,
                'placeholder' => 'Оберіть фільтр',
                'choices' => $filters,
                'label' => 'Фільтр',
                'required' => true,
                'attr' => [
                    'class' => 'filter'
                ]
            ])
        ;

        $formModifier = function (FormInterface $form, Filter $filter = null) {
            $attributes = null === $filter ? [] : $filter->getFilterAttributes();

            $form->add('filterAttribute', EntityType::class, [
                'class' => FilterAttribute::class,
                'placeholder' => 'Оберіть параметр',
                'choices' => $attributes,
                'label' => 'Параметр',
                'required' => true,
                'attr' => [
                    'class' => 'filter-attribute'
                ]
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();

                $formModifier($event->getForm(), $data?->getFilter());
            }
        );

        $builder->get('filter')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $filter = $event->getForm()->getData();

                // since we've added the listener to the child, we'll have to pass on
                // the parent to the callback function!
                $formModifier($event->getForm()->getParent(), $filter);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => ProductFilterAttribute::class,
        ]);
    }
}
