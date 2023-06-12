<?php

namespace App\Form;

use App\Entity\Filter;
use App\Entity\FilterAttribute;
use App\Entity\ProductFilterAttribute;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilterAttributeType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filter', EntityType::class, [
                'class' => Filter::class,
                'placeholder' => '',
                'label' => 'Фільтр',
                'required' => true
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
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                // this would be your entity, i.e. SportMeetup
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
        $resolver->setDefaults([
            'data_class' => ProductFilterAttribute::class,
        ]);
    }
}
