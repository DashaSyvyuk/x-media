<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Category;
use App\Tests\Traits\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group controller
 * @group smoke
 */
class CategoryPageControllerTest extends WebTestCase
{
    use FixturesTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    public function testCategoryPageWithValidCategoryIsAccessible(): void
    {
        $client = static::createClient();

        // Load fixtures after client is created
        self::getContainer()->get('kernel')->boot();
        $this->loadFixtures();

        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Use notebooks category from fixtures
        $categoryRepository = $entityManager->getRepository(Category::class);
        $category = $categoryRepository->findOneBy(['slug' => 'notebooks']);
        $this->assertNotNull($category);

        // Make request to category page
        $client->request('GET', '/categories/' . $category->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testCategoryPageWithInvalidSlugReturnsNotFound(): void
    {
        $client = static::createClient();

        $nonExistentSlug = 'this-category-does-not-exist-' . time();
        $client->request('GET', '/categories/' . $nonExistentSlug);

        // The application returns 200 with a "not found page" content instead of 404
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Not found page');
    }
}
