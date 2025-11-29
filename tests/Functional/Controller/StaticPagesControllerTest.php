<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group controller
 * @group smoke
 */
class StaticPagesControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    /**
     * @dataProvider staticPagesProvider
     */
    public function testStaticPagesAreAccessible(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function staticPagesProvider(): array
    {
        return [
            'about us page' => ['/about-us'],
            'contact page' => ['/contact'],
            'delivery and pay page' => ['/delivery-and-pay'],
            'warranty page' => ['/warranty'],
        ];
    }

    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'Login page should be accessible or redirect'
        );
    }

    public function testSearchPageIsAccessible(): void
    {
        $client = static::createClient();

        // Test that search page loads without errors
        // Note: Full search functionality requires complex database setup with
        // product characteristics, filters, and other related entities.
        // The fixtures provide basic data (products, categories, settings) which
        // is sufficient for basic page loading tests.
        $client->request('GET', '/search?search=test');

        // We check for either success or redirect (app may redirect on empty search)
        $response = $client->getResponse();
        $statusCode = $response->getStatusCode();

        $this->assertTrue(
            $statusCode === 200 || $statusCode === 302,
            "Search page should be accessible (got status code: $statusCode)"
        );
    }
}
