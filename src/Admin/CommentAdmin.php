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
            ->add('author', null, ['label' => 'Автор', 'required' => true])
            ->add('email', null, ['label' => 'Email', 'required' => true])
            ->add('comment', null, ['label' => 'Коментар', 'required' => true])
            ->add('answer', null, ['label' => 'Відповідь'])
        ;
    }
}
