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
        // Don't test search page as it requires complex database setup
        // This should be tested separately as a search functionality test
        $this->markTestSkipped('Search page requires additional database fixtures for products and categories');
    }
}
