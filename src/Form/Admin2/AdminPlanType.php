<?php

namespace App\Form\Admin2;

use App\Entity\AdminPlan;
use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminPlanType extends AbstractType
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AdminUser|null $currentUser */
        $currentUser = $options['current_user'];
        $choices = $this->adminUserRepository->findActiveOrderedByName();

        if ($currentUser instanceof AdminUser) {
            $found = false;
            foreach ($choices as $choice) {
                if ($choice->getId() === $currentUser->getId()) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                array_unshift($choices, $currentUser);
            }
        }

        $builder
            ->add('scheduledDate', DateType::class, [
                'label'    => 'Дата',
                'widget'   => 'single_text',
                'input'    => 'datetime',
                'required' => true,
            ])
            ->add('assignee', EntityType::class, [
                'label'        => 'Призначити',
                'class'        => AdminUser::class,
                'choices'      => $choices,
                'choice_label' => static function (AdminUser $user): string {
                    $label = trim($user->getName() . ' ' . $user->getSurname());

                    return $label !== '' ? $label : (string) $user->getEmail();
                },
                'required'     => true,
            ])
            ->add('title', TextType::class, [
                'label'       => 'Назва',
                'constraints' => [new NotBlank()],
            ])
            ->add('body', TextareaType::class, [
                'label'    => 'Текст',
                'required' => false,
                'attr'     => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => AdminPlan::class,
            'current_user' => null,
        ]);
        $resolver->setAllowedTypes('current_user', ['null', AdminUser::class]);
    }
}
