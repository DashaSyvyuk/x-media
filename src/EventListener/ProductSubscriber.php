<?php

namespace App\EventListener;

use App\Entity\Feed;
use App\Entity\Product;
use App\Entity\RozetkaProduct;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\FeedRepository;
use App\Repository\RozetkaProductRepository;
use App\Service\PriceTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Product::class)]
#[AsDoctrineListener(event: Events::postFlush)]
class ProductSubscriber
{
    use PriceTrait;

    /** @var list<Product> */
    private array $productsNeedingRozetka = [];

    private bool $syncScheduled = false;

    private bool $flushing = false;

    public function __construct(
        private readonly RozetkaProductRepository $repository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function postUpdate(Product $product): void
    {
        $rozetkaProduct = $product->getRozetka();
        if ($rozetkaProduct === null) {
            $this->productsNeedingRozetka[] = $product;
            $this->syncScheduled = true;

            return;
        }

        $title = $this->buildTitle($product);
        if ($rozetkaProduct->getTitle() !== $title) {
            $rozetkaProduct->setTitle($title);
            $this->syncScheduled = true;
        }

        $description = (string) ($product->getDescription() ?? '');
        if ($rozetkaProduct->getDescription() === '' && $description !== '') {
            $rozetkaProduct->setDescription($description);
            $this->syncScheduled = true;
        }
    }

    public function postPersist(Product $product): void
    {
        if ($product->getRozetka() !== null) {
            return;
        }

        $this->productsNeedingRozetka[] = $product;
        $this->syncScheduled = true;
    }

    public function postFlush(): void
    {
        if ($this->flushing || ! $this->syncScheduled) {
            return;
        }

        $this->syncScheduled = false;
        $productsToCreate = $this->productsNeedingRozetka;
        $this->productsNeedingRozetka = [];

        $this->flushing = true;
        try {
            foreach ($productsToCreate as $product) {
                if (! $this->entityManager->contains($product)) {
                    continue;
                }

                if ($product->getRozetka() !== null) {
                    continue;
                }

                if ($this->repository->findByAttachedProductId($product->getId()) !== null) {
                    continue;
                }

                $this->createRozetkaFor($product);
            }

            $this->entityManager->flush();
        } finally {
            $this->flushing = false;
        }
    }

    private function createRozetkaFor(Product $product): void
    {
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_ROZETKA]);
        $priceParameters = $feed
            ? $this->categoryFeedPriceRepository->findOneBy([
                'feed'     => $feed,
                'category' => $product->getCategory(),
            ])
            : null;

        $rozetkaProduct = new RozetkaProduct();
        $rozetkaProduct->setTitle($this->buildTitle($product));
        $rozetkaProduct->setStockQuantity(0);
        $rozetkaProduct->setSeries('');
        $rozetkaProduct->setDescription((string) ($product->getDescription() ?? ''));
        $rozetkaProduct->setPrice((int) $this->getPrice($product, $feed, $priceParameters));
        $rozetkaProduct->setProduct($product);
        $product->setRozetka($rozetkaProduct);

        $this->entityManager->persist($rozetkaProduct);
    }

    private function buildTitle(Product $product): string
    {
        return RozetkaProduct::formatMarketplaceTitle(
            $product->getTitle(),
            $product->getProductCode(),
        );
    }
}
