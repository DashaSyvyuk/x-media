<?php

namespace App\Form\Admin2;

use App\Entity\Category;
use App\Entity\Feed;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $excludeCategoryId = $options['exclude_category_id'];

        $builder
            ->add('parent', EntityType::class, [
                'class'         => Category::class,
                'label'         => 'Батьківська категорія',
                'placeholder'   => 'Без батьківської',
                'required'      => false,
                'query_builder' => function (EntityRepository $repository) use ($excludeCategoryId) {
                    $qb = $repository->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($excludeCategoryId !== null) {
                        $qb->andWhere('c.id != :excludeId')->setParameter('excludeId', $excludeCategoryId);
                    }

                    return $qb;
                },
            ])
            ->add('title', TextType::class, ['label' => 'Назва'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('status', SwitchType::class, [
                'label'     => 'Активний',
                'on_value'  => Category::ACTIVE,
                'off_value' => Category::DISABLED,
            ])
            ->add('position', IntegerType::class, ['label' => 'Пріоритет', 'required' => false])
            ->add('imageFile', FileType::class, [
                'label'       => 'Картинка',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'maxSize'   => '5M',
                    ]),
                ],
            ])
            ->add('showInHeader', SwitchType::class, [
                'label'     => 'Показувати в хедері',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('metaKeyword', TextareaType::class, [
                'label'    => 'Ключові слова',
                'required' => false,
            ])
            ->add('metaDescription', TextareaType::class, [
                'label'    => 'Опис (meta)',
                'required' => false,
            ])
            ->add('hotlineCategory', TextType::class, [
                'label'    => 'Категорія Hotline',
                'required' => false,
                'help'     => 'Напр. Ноутбуки і планшети',
            ])
            ->add('rozetkaCategory', TextType::class, [
                'label'    => 'Категорія Rozetka',
                'required' => false,
                'help'     => 'Напр. Смарт-годинник',
            ])
            ->add('promCategoryLink', TextType::class, [
                'label'    => 'Посилання Prom',
                'required' => false,
                'help'     => 'https://prom.ua/Noutbuki',
            ])
            ->add('showInEkatalogFeed', SwitchType::class, [
                'label'     => 'E-Katalog feed',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('showInPromFeed', SwitchType::class, [
                'label'     => 'Prom feed',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('showInRozetkaFeed', SwitchType::class, [
                'label'     => 'Rozetka feed',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('showInHotlineFeed', SwitchType::class, [
                'label'     => 'Hotline feed',
                'on_value'  => true,
                'off_value' => false,
            ])
            ->add('feedPrices', CollectionType::class, [
                'entry_type'    => CategoryFeedPriceEntryType::class,
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
            'data_class'          => Category::class,
            'exclude_category_id' => null,
        ]);

        $resolver->setAllowedTypes('exclude_category_id', ['null', 'int']);
    }
}
