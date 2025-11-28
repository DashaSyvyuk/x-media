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
        $crawler = $client->request('GET', '/category/' . $uniqueSlug);

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
        $client->request('GET', '/category/' . $nonExistentSlug);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
