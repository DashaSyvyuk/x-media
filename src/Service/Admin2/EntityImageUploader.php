<?php

namespace App\Service\Admin2;

use App\Entity\Category;
use App\Entity\DeliveryType;
use App\Entity\PaymentType;
use App\Entity\Slider;
use App\EventListener\ImageUploadSubscriber;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class EntityImageUploader
{
    /** @var array<class-string, array{folder: string, prefix: string}> */
    private const ENTITIES = [
        Category::class     => ['folder' => 'category', 'prefix' => 'cat_'],
        Slider::class       => ['folder' => 'slider',   'prefix' => 'slider_'],
        PaymentType::class  => ['folder' => 'payment',  'prefix' => 'pay_'],
        DeliveryType::class => ['folder' => 'delivery', 'prefix' => 'del_'],
    ];

    public function __construct(
        private ImageUploadSubscriber $imageUploadSubscriber,
        private string $publicImagesDir,
    ) {
    }

    public function upload(UploadedFile $file, string $entityClass): string
    {
        if (! isset(self::ENTITIES[$entityClass])) {
            throw new InvalidArgumentException(sprintf('Image upload is not configured for %s.', $entityClass));
        }

        $config = self::ENTITIES[$entityClass];
        $this->imageUploadSubscriber->ensureLocalDirFor($entityClass);

        $extension = $file->guessExtension() ?: 'jpg';
        $filename  = uniqid($config['prefix'], true) . '.' . $extension;

        $file->move($this->publicImagesDir . '/' . $config['folder'], $filename);

        return $filename;
    }
}
