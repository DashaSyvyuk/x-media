<?php

namespace App\EventListener;

use App\Entity\Feed;
use App\Entity\Product;
use App\Entity\RozetkaProduct;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\FeedRepository;
use App\Repository\RozetkaProductRepository;
use App\Service\PriceTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Exception;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Product::class)]
class ProductSubscriber
{
    use PriceTrait;

    public function __construct(
        private readonly RozetkaProductRepository $repository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository
    )
    {
    }

    /**
     * @throws Exception
     */
    public function postUpdate(Product $product): void
    {
        $rozetkaProduct = $product->getRozetka();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_ROZETKA]);
        $priceParameters = $feed ? $this->categoryFeedPriceRepository->findOneBy(['feed' => $feed, 'category' => $product->getCategory()]) : null;

        $rozetkaProduct->setTitle($product->getTitle());

        if (!$rozetkaProduct->getDescription()) {
            $rozetkaProduct->setDescription($product->getDescription());
        }

        $rozetkaProduct->setPrice($this->getPrice($product, $feed, $priceParameters));

        $this->repository->update($rozetkaProduct);
    }

    /**
     * @throws Exception
     */
    public function postPersist(Product $product): void
    {
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_ROZETKA]);
        $priceParameters = $feed ? $this->categoryFeedPriceRepository->findOneBy(['feed' => $feed, 'category' => $product->getCategory()]) : null;

        $rozetkaProduct = new RozetkaProduct();
        $rozetkaProduct->setTitle($product->getTitle());
        $rozetkaProduct->setStockQuantity(0);
        $rozetkaProduct->setArticle('');
        $rozetkaProduct->setSeries('');
        $rozetkaProduct->setDescription($product->getDescription());
        $rozetkaProduct->setPrice($this->getPrice($product, $feed, $priceParameters));
        $rozetkaProduct->setProduct($product);

        $this->repository->create($rozetkaProduct);
    }
}
