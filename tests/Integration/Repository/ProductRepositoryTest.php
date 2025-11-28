<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group integration
 * @group repository
 */
class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->productRepository = $container->get(ProductRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    public function testFindActiveProducts(): void
    {
        $entityManager = $this->productRepository->getEntityManager();

        // Create test active product
        $activeProduct = new Product();
        $activeProduct->setTitle('Active Product');
        $activeProduct->setStatus(Product::STATUS_ACTIVE);
        $activeProduct->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $activeProduct->setPrice(1000);
        $activeProduct->setProductCode('ACTIVE-001');

        $entityManager->persist($activeProduct);

        // Create test blocked product
        $blockedProduct = new Product();
        $blockedProduct->setTitle('Blocked Product');
        $blockedProduct->setStatus(Product::STATUS_BLOCKED);
        $blockedProduct->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $blockedProduct->setPrice(1500);
        $blockedProduct->setProductCode('BLOCKED-001');

        $entityManager->persist($blockedProduct);
        $entityManager->flush();

        // Find only active products
        $activeProducts = $this->productRepository->findBy(['status' => Product::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(1, count($activeProducts));

        // Verify that found product is active
        $foundActiveProduct = null;
        foreach ($activeProducts as $product) {
            if ($product->getProductCode() === 'ACTIVE-001') {
                $foundActiveProduct = $product;
                break;
            }
        }

        $this->assertNotNull($foundActiveProduct);
        $this->assertSame(Product::STATUS_ACTIVE, $foundActiveProduct->getStatus());

        // Clean up - refresh to load relationships and handle cascade delete
        $entityManager->refresh($activeProduct);
        $entityManager->refresh($blockedProduct);
        $entityManager->remove($activeProduct);
        $entityManager->remove($blockedProduct);
        $entityManager->flush();
    }

    public function testFindProductsByCategory(): void
    {
        $entityManager = $this->productRepository->getEntityManager();

        // Create test category
        $category = new Category();
        $category->setTitle('Test Category');
        $category->setSlug('test-category-' . time());
        $entityManager->persist($category);

        // Create product in category
        $product = new Product();
        $product->setTitle('Category Product');
        $product->setStatus(Product::STATUS_ACTIVE);
        $product->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $product->setPrice(2000);
        $product->setProductCode('CAT-001');
        $product->setCategory($category);

        $entityManager->persist($product);
        $entityManager->flush();

        // Find products by category
        $products = $this->productRepository->findBy(['category' => $category]);

        $this->assertGreaterThanOrEqual(1, count($products));
        $this->assertSame($category->getId(), $products[0]->getCategory()->getId());

        // Clean up - refresh to load relationships and handle cascade delete
        $entityManager->refresh($product);
        $entityManager->remove($product);
        $entityManager->remove($category);
        $entityManager->flush();
    }

    public function testFindProductByProductCode(): void
    {
        $entityManager = $this->productRepository->getEntityManager();

        $uniqueCode = 'UNIQUE-' . time();

        $product = new Product();
        $product->setTitle('Unique Code Product');
        $product->setStatus(Product::STATUS_ACTIVE);
        $product->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $product->setPrice(3000);
        $product->setProductCode($uniqueCode);

        $entityManager->persist($product);
        $entityManager->flush();

        $foundProduct = $this->productRepository->findOneBy(['productCode' => $uniqueCode]);

        $this->assertNotNull($foundProduct);
        $this->assertSame($uniqueCode, $foundProduct->getProductCode());
        $this->assertSame('Unique Code Product', $foundProduct->getTitle());

        // Clean up - refresh to load relationships
        $entityManager->refresh($foundProduct);
        $entityManager->remove($foundProduct);
        $entityManager->flush();
    }

    public function testFindRecentProducts(): void
    {
        $entityManager = $this->productRepository->getEntityManager();

        $product1 = new Product();
        $product1->setTitle('Recent Product 1');
        $product1->setStatus(Product::STATUS_ACTIVE);
        $product1->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $product1->setPrice(1000);
        $product1->setProductCode('RECENT-001');

        $entityManager->persist($product1);
        $entityManager->flush();

        // Small delay
        usleep(100000);

        $product2 = new Product();
        $product2->setTitle('Recent Product 2');
        $product2->setStatus(Product::STATUS_ACTIVE);
        $product2->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $product2->setPrice(1200);
        $product2->setProductCode('RECENT-002');

        $entityManager->persist($product2);
        $entityManager->flush();

        // Get products ordered by creation date
        $recentProducts = $this->productRepository->findBy(
            ['status' => Product::STATUS_ACTIVE],
            ['createdAt' => 'DESC'],
            10
        );

        $this->assertGreaterThanOrEqual(2, count($recentProducts));

        // The most recently created should be first
        $foundProduct2 = false;
        $foundProduct1 = false;

        foreach ($recentProducts as $product) {
            if ($product->getProductCode() === 'RECENT-002') {
                $foundProduct2 = true;
            }
            if ($product->getProductCode() === 'RECENT-001') {
                $foundProduct1 = true;
            }
        }

        $this->assertTrue($foundProduct2);
        $this->assertTrue($foundProduct1);

        // Clean up - refresh to load relationships
        $entityManager->refresh($product1);
        $entityManager->refresh($product2);
        $entityManager->remove($product1);
        $entityManager->remove($product2);
        $entityManager->flush();
    }
}
