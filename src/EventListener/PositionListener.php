<?php

namespace App\EventListener;

use App\Entity\Category;
use App\Entity\ProductCharacteristic;
use App\Entity\ProductImage;
use App\Repository\CategoryRepository;
use App\Repository\ProductCharacteristicRepository;
use App\Repository\ProductImageRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class PositionListener
{
    public function __construct(
        private readonly ProductImageRepository $productImageRepository,
        private readonly ProductCharacteristicRepository $productCharacteristicRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        if ($entity instanceof ProductImage) {
            $productImage = $this->productImageRepository->findOneBy(
                ['product' => $entity->getProduct()],
                ['position' => 'DESC']
            );

            $entity->setPosition($productImage->getPosition() + 1);
            $entityManager->flush();
        }

        if ($entity instanceof ProductCharacteristic) {
            $productCharacteristic = $this->productCharacteristicRepository->findOneBy(
                ['product' => $entity->getProduct()],
                ['position' => 'DESC']
            );

            $entity->setPosition($productCharacteristic->getPosition() + 1);
            $entityManager->flush();
        }

        if ($entity instanceof Category) {
            $category = $this->categoryRepository->findOneBy(
                ['parent' => $entity->getParent()],
                ['position' => 'DESC']
            );

            $entity->setPosition($category->getPosition() + 1);
            $entityManager->flush();
        }
    }
}
