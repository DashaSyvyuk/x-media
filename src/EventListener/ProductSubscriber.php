<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\RozetkaProduct;
use App\Repository\RozetkaProductRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Exception;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Product::class)]
class ProductSubscriber
{
    public function __construct(
        private readonly RozetkaProductRepository $repository,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function postUpdate(Product $product): void
    {
        $rozetkaProduct = $product->getRozetka();

        if (!$rozetkaProduct->getTitle()) {
            $rozetkaProduct->setTitle($product->getTitle());
        }

        if (!$rozetkaProduct->getDescription()) {
            $rozetkaProduct->setDescription($product->getDescription());
        }

        if (!$rozetkaProduct->getPrice()) {
            $rozetkaProduct->setPrice($product->getPrice());
        }

        $this->repository->update($rozetkaProduct);
    }

    /**
     * @throws Exception
     */
    public function postPersist(Product $product): void
    {
        $rozetkaProduct = new RozetkaProduct();
        $rozetkaProduct->setTitle($product->getTitle());
        $rozetkaProduct->setStockQuantity(0);
        $rozetkaProduct->setArticle('');
        $rozetkaProduct->setSeries('');
        $rozetkaProduct->setDescription($product->getDescription());
        $rozetkaProduct->setPrice($product->getPrice());
        $rozetkaProduct->setProduct($product);

        $this->repository->create($rozetkaProduct);
    }
}
