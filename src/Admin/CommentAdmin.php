<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CommentAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Активний' => 'ACTIVE',
                    'Заблокований' => 'DISABLED'
                ],
                'label' => 'Статус'
            ])
            ->add('author', null, ['label' => 'Автор'])
            ->add('email', null, ['label' => 'Email'])
            ->add('comment', null, ['label' => 'Коментар'])
            ->add('answer', null, ['label' => 'Відповідь'])
        ;
    }
}
