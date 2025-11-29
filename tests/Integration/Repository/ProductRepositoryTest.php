<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\Traits\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group integration
 * @group repository
 */
class ProductRepositoryTest extends KernelTestCase
{
    use FixturesTrait;

    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->loadFixtures();
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

        // Create test blocked product to verify filtering
        $blockedProduct = new Product();
        $blockedProduct->setTitle('Blocked Product');
        $blockedProduct->setStatus(Product::STATUS_BLOCKED);
        $blockedProduct->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $blockedProduct->setPrice(1500);
        $blockedProduct->setProductCode('BLOCKED-001');

        $entityManager->persist($blockedProduct);
        $entityManager->flush();

        // Find only active products (fixtures loaded 7 active products)
        $activeProducts = $this->productRepository->findBy(['status' => Product::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(7, count($activeProducts));

        // Verify that MacBook Pro from fixtures is active
        $foundMacBook = null;
        foreach ($activeProducts as $product) {
            if ($product->getProductCode() === 'MBP-16-001') {
                $foundMacBook = $product;
                break;
            }
        }

        $this->assertNotNull($foundMacBook);
        $this->assertSame(Product::STATUS_ACTIVE, $foundMacBook->getStatus());
        $this->assertSame('Apple MacBook Pro 16', $foundMacBook->getTitle());

        // No cleanup needed - fixtures will be reloaded for the next test
    }

    public function testFindProductsByCategory(): void
    {
        $entityManager = $this->productRepository->getEntityManager();

        // Use the notebooks category from fixtures
        $categoryRepository = $entityManager->getRepository(Category::class);
        $notebooksCategory = $categoryRepository->findOneBy(['slug' => 'notebooks']);

        $this->assertNotNull($notebooksCategory);

        // Find products by category (fixtures have 3 notebooks)
        $products = $this->productRepository->findBy(['category' => $notebooksCategory]);

        $this->assertGreaterThanOrEqual(3, count($products));

        // Verify products are in the correct category
        foreach ($products as $product) {
            $this->assertSame($notebooksCategory->getId(), $product->getCategory()->getId());
        }

        // Verify specific products from fixtures
        $productCodes = array_map(fn($p) => $p->getProductCode(), $products);
        $this->assertContains('MBP-16-001', $productCodes);
        $this->assertContains('LEN-X1-002', $productCodes);
        $this->assertContains('DELL-XPS-003', $productCodes);
    }

    public function testFindProductByProductCode(): void
    {
        // Use iPhone from fixtures
        $foundProduct = $this->productRepository->findOneBy(['productCode' => 'IPH-15P-004']);

        $this->assertNotNull($foundProduct);
        $this->assertSame('IPH-15P-004', $foundProduct->getProductCode());
        $this->assertSame('iPhone 15 Pro', $foundProduct->getTitle());
        $this->assertSame(42999, $foundProduct->getPrice());
    }

    public function testFindRecentProducts(): void
    {
        // Get products ordered by creation date (fixtures loaded 7 products)
        $recentProducts = $this->productRepository->findBy(
            ['status' => Product::STATUS_ACTIVE],
            ['createdAt' => 'DESC'],
            10
        );

        $this->assertGreaterThanOrEqual(7, count($recentProducts));

        // Verify products from fixtures are in results
        $productCodes = array_map(fn($p) => $p->getProductCode(), $recentProducts);

        $this->assertContains('MBP-16-001', $productCodes);
        $this->assertContains('IPH-15P-004', $productCodes);
        $this->assertContains('IPAD-PRO-006', $productCodes);

        // Verify all returned products are active
        foreach ($recentProducts as $product) {
            $this->assertSame(Product::STATUS_ACTIVE, $product->getStatus());
        }
    }
}
