<?php

namespace App\Form;

use App\Entity\ProductImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', VichImageType::class, [
                'constraints' => [
                    new Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        // Keep in sync with docker/php/uploads.ini + nginx client_max_body_size.
                        'maxSize'   => '12M',
                    ]),
                ],
                'download_uri' => false,
                'required'     => false,
                'allow_delete' => false,
                'image_uri'    => static function ($entity): ?string {
                    return $entity instanceof ProductImage ? $entity->getImageUri() : null;
                },
            ])
            ->add('position')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductImage::class,
        ]);
    }
}
