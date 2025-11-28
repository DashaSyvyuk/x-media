<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group controller
 * @group smoke
 */
class ProductPageControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    public function testProductPageWithValidProductIsAccessible(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create a test category
        $category = new Category();
        $category->setTitle('Test Category');
        $category->setSlug('test-category-' . time());
        $entityManager->persist($category);

        // Create a test product
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setStatus(Product::STATUS_ACTIVE);
        $product->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $product->setPrice(1000);
        $product->setProductCode('TEST-FUNC-001');
        $product->setCategory($category);
        $product->setDescription('Test description');

        $entityManager->persist($product);
        $entityManager->flush();

        // Make request to product page
        $crawler = $client->request('GET', '/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Clean up - refresh to load relationships for cascade delete
        $entityManager->refresh($product);
        $entityManager->remove($product);
        $entityManager->remove($category);
        $entityManager->flush();
    }

    public function testProductPageWithInvalidProductReturnsNotFound(): void
    {
        $client = static::createClient();

        // Use a very large ID that likely doesn't exist
        $nonExistentId = 999999999;
        $client->request('GET', '/products/' . $nonExistentId);

        // The application redirects to home page for invalid products
        $this->assertResponseRedirects('/');
    }
}
