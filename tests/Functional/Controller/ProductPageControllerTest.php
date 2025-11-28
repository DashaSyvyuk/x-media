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
        $crawler = $client->request('GET', '/product/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Clean up
        $entityManager->remove($product);
        $entityManager->remove($category);
        $entityManager->flush();
    }

    public function testProductPageWithInvalidProductReturnsNotFound(): void
    {
        $client = static::createClient();

        // Use a very large ID that likely doesn't exist
        $nonExistentId = 999999999;
        $client->request('GET', '/product/' . $nonExistentId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
