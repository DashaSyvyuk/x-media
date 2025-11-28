<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group controller
 * @group smoke
 */
class CategoryPageControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    public function testCategoryPageWithValidCategoryIsAccessible(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create a test category
        $category = new Category();
        $category->setTitle('Test Category for Page');
        $uniqueSlug = 'test-category-page-' . time();
        $category->setSlug($uniqueSlug);

        $entityManager->persist($category);
        $entityManager->flush();

        // Make request to category page
        $client->request('GET', '/categories/' . $uniqueSlug);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Clean up
        $entityManager->remove($category);
        $entityManager->flush();
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
