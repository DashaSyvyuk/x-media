<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group controller
 * @group smoke
 */
class HomePageControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testHomePageContainsExpectedElements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Check that the page has HTML structure
        $this->assertSelectorExists('html');
        $this->assertSelectorExists('body');
    }

    public function testHomePageReturnsHtmlContent(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'text/html; charset=UTF-8'),
            'Response should contain HTML content type'
        );
    }
}
