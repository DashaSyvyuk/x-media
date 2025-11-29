<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Tests\Traits\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group controller
 * @group smoke
 */
class ProductPageControllerTest extends WebTestCase
{
    use FixturesTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    public function testProductPageWithValidProductIsAccessible(): void
    {
        $client = static::createClient();

        // Load fixtures after client is created
        self::getContainer()->get('kernel')->boot();
        $this->loadFixtures();

        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Use MacBook Pro from fixtures
        $productRepository = $entityManager->getRepository(Product::class);
        $product = $productRepository->findOneBy(['productCode' => 'MBP-16-001']);
        $this->assertNotNull($product);

        // Make request to product page
        $client->request('GET', '/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
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
