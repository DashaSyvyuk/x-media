<?php

namespace App\Tests\Unit\Service\Order;

use App\Entity\Order;
use App\Entity\Setting;
use App\Repository\SettingRepository;
use App\Service\Order\CreateService;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Test for Order CreateService with best practices
 *
 * Note: TurboSms::send() is stubbed using tests/Stubs/TurboSms.php which is loaded
 * in tests/bootstrap.php. This prevents real HTTP requests while allowing us to verify
 * that SMS functionality is called correctly.
 *
 * @group unit
 * @group service
 * @group order
 * @group critical
 * @group email
 */
class OrderCreateServiceTest extends TestCase
{
    private CreateService $service;
    /** @var SettingRepository&\PHPUnit\Framework\MockObject\MockObject */
    private SettingRepository $settingRepository;
    /** @var MailerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private MailerInterface $mailer;
    /** @var Environment&\PHPUnit\Framework\MockObject\MockObject */
    private Environment $twig;
    private Setting $emailSetting;
    private Setting $phoneSetting;

    /**
     * Setup method - runs before each test
     * Best practice: Initialize common test dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);

        // Setup common test data
        $this->emailSetting = new Setting();
        $this->emailSetting->setSlug('email');
        $this->emailSetting->setValue('manager@xmedia.com');

        $this->phoneSetting = new Setting();
        $this->phoneSetting->setSlug('phone_number');
        $this->phoneSetting->setValue('+380501234567');

        // Setup environment variables for TurboSms stub
        // The stub is loaded in tests/bootstrap.php and doesn't make real HTTP requests
        $_SERVER['TURBO_SMS_ENDPOINT'] = 'https://api.turbosms.test';
        $_SERVER['TURBO_SMS_TOKEN'] = 'test-token';
        $_SERVER['TURBO_SMS_SENDER'] = 'TestSender';

        // Reset TurboSms stub state before each test
        \App\Utils\TurboSms::reset();

        // Create service instance
        $this->service = new CreateService(
            $this->twig,
            $this->settingRepository,
            $this->mailer
        );
    }

    /**
     * Teardown method - runs after each test
     * Best practice: Clean up resources
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up environment variables
        unset(
            $_SERVER['TURBO_SMS_ENDPOINT'],
            $_SERVER['TURBO_SMS_TOKEN'],
            $_SERVER['TURBO_SMS_SENDER']
        );

        // Clean up any resources if needed
        unset(
            $this->service,
            $this->container,
            $this->settingRepository,
            $this->mailer,
            $this->twig,
            $this->emailSetting,
            $this->phoneSetting
        );
    }

    /**
     * Test service sends email to customer with order
     *
     * Note: TurboSms::send() is stubbed in tests/Stubs/TurboSms.php to prevent
     * real HTTP requests. The stub is loaded in tests/bootstrap.php.
     *
     * @group email
     */
    public function testRunSendsEmailToCustomerWhenOrderHasEmail(): void
    {
        // Arrange
        $order = $this->createOrderWithEmail();
        $host = 'xmedia.com';

        $this->setupSettingRepositoryMock();
        $this->setupTwigMock();

        // Assert - using argument constraints matcher
        $this->mailer->expects($this->exactly(2))
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        // Act
        $this->service->run($order, $host);

        // Assert - verify SMS was sent via the stub
        $this->assertTrue(\App\Utils\TurboSms::$wasCalled, 'TurboSms::send should have been called');
        $this->assertEquals($order->getPhone(), \App\Utils\TurboSms::$lastRecipient);
    }

    /**
     * Test service throws exception when manager email is missing
     *
     * @group validation
     * @group critical
     */
    public function testRunThrowsExceptionWhenManagerEmailIsMissing(): void
    {
        // Arrange
        $order = $this->createOrderWithEmail();

        $this->settingRepository->method('findOneBy')
            ->with(['slug' => 'email'])
            ->willReturn(null);

        // Assert - using expectException matcher
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service manager email is missing');

        // Act
        $this->service->run($order, 'xmedia.com');
    }

    /**
     * Test service does not send customer email when order has no email
     *
     * @group email
     */
    public function testRunDoesNotSendCustomerEmailWhenOrderHasNoEmail(): void
    {
        // Arrange
        $order = $this->createOrderWithoutEmail();

        $this->setupSettingRepositoryMock();
        $this->setupTwigMock();

        // Assert - using exactly(1) matcher - only manager email sent
        $this->mailer->expects($this->exactly(1))
            ->method('send');

        // Act
        $this->service->run($order, 'xmedia.com');

        // Assert - verify SMS was sent (order has phone even without email)
        $this->assertTrue(\App\Utils\TurboSms::$wasCalled, 'TurboSms::send should have been called');
    }

    /**
     * Test service generates correct URL from host
     *
     * @group url
     */
    public function testRunGeneratesCorrectUrlFromHost(): void
    {
        // Arrange
        $order = $this->createOrderWithEmail();
        $host = 'test.xmedia.com';
        $expectedUrl = 'https://test.xmedia.com/';

        $this->setupSettingRepositoryMock();

        // Assert - using callback matcher to check URL generation
        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function ($context) use ($expectedUrl) {
                    return isset($context['mainUrl']) && $context['mainUrl'] === $expectedUrl;
                })
            )
            ->willReturn('<html>Test Email</html>');

        // Act
        $this->service->run($order, $host);
    }

    /**
     * Test email contains order number in subject
     *
     * @group email
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('orderNumberProvider')]
    public function testEmailSubjectContainsOrderNumber(string $orderNumber): void
    {
        // Arrange
        $order = $this->createOrderWithEmail();
        $order->setOrderNumber($orderNumber);

        $this->setupSettingRepositoryMock();
        $this->setupTwigMock();

        // Assert - using stringContains matcher
        $this->mailer->expects($this->exactly(2))
            ->method('send')
            ->with($this->callback(function (Email $email) use ($orderNumber) {
                return str_contains($email->getSubject(), $orderNumber);
            }));

        // Act
        $this->service->run($order, 'xmedia.com');
    }

    /**
     * Data provider for order numbers
     * Best practice: Use data providers for parameterized tests
     * @return array<string, array{string}>
     */
    public static function orderNumberProvider(): array
    {
        return [
            'timestamp format' => ['1234567890'],
            'alphanumeric' => ['ORD-12345'],
            'with prefix' => ['XM-2024-001'],
        ];
    }

    /**
     * Test email is sent from correct address
     *
     * @group email
     */
    public function testEmailIsSentFromCorrectAddress(): void
    {
        // Arrange
        $order = $this->createOrderWithEmail();
        $expectedFrom = 'x-media@x-media.com.ua';

        $this->setupSettingRepositoryMock();
        $this->setupTwigMock();

        // Assert - using custom matcher for email address
        $this->mailer->expects($this->exactly(2))
            ->method('send')
            ->with($this->emailFromAddress($expectedFrom));

        // Act
        $this->service->run($order, 'xmedia.com');
    }

    /**
     * Test manager email is sent with correct data
     *
     * @group email
     * @group manager
     */
    public function testManagerEmailIsSentWithCorrectData(): void
    {
        // Arrange
        $order = $this->createOrderWithEmail();

        $this->setupSettingRepositoryMock();

        // Track render calls to verify both templates are rendered
        $renderCalls = [];
        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($template, $context) use (&$renderCalls) {
                $renderCalls[] = ['template' => $template, 'context' => $context];
                return '<html>Test Email</html>';
            });

        // Act
        $this->service->run($order, 'xmedia.com');

        // Assert - verify both emails are rendered with correct data
        $this->assertCount(2, $renderCalls);

        // First call: customer email
        $this->assertEquals('emails/client-orders.html.twig', $renderCalls[0]['template']);
        $this->assertArrayHasKey('order', $renderCalls[0]['context']);

        // Second call: manager email
        $this->assertEquals('emails/manager-order.html.twig', $renderCalls[1]['template']);
        $this->assertArrayHasKey('order', $renderCalls[1]['context']);
        $this->assertArrayHasKey('mainUrl', $renderCalls[1]['context']);
    }

    /**
     * Test customer email includes phone number setting
     *
     * @group email
     */
    public function testCustomerEmailIncludesPhoneNumberSetting(): void
    {
        // Arrange
        $order = $this->createOrderWithEmail();

        // Setup setting repository to return both email and phone
        $this->settingRepository->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if ($criteria['slug'] === 'email') {
                    return $this->emailSetting;
                }
                if ($criteria['slug'] === 'phone_number') {
                    return $this->phoneSetting;
                }
                return null;
            });

        // Track render calls to verify both templates are rendered with correct data
        $renderCalls = [];
        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($template, $context) use (&$renderCalls) {
                $renderCalls[] = ['template' => $template, 'context' => $context];
                return '<html>Test Email</html>';
            });

        // Act
        $this->service->run($order, 'xmedia.com');

        // Assert - verify customer email has phoneNumber
        $this->assertCount(2, $renderCalls);
        $this->assertEquals('emails/client-orders.html.twig', $renderCalls[0]['template']);
        $this->assertArrayHasKey('phoneNumber', $renderCalls[0]['context']);
        $this->assertArrayHasKey('order', $renderCalls[0]['context']);

        // Assert - verify manager email has expected keys (but not phoneNumber)
        $this->assertEquals('emails/manager-order.html.twig', $renderCalls[1]['template']);
        $this->assertArrayHasKey('order', $renderCalls[1]['context']);
        $this->assertArrayHasKey('mainUrl', $renderCalls[1]['context']);
    }

    // ========================================
    // Helper Methods (Best Practice)
    // ========================================

    /**
     * Create test order with email
     */
    private function createOrderWithEmail(): Order
    {
        $order = new Order();
        $order->setOrderNumber('TEST-123');
        $order->setEmail('customer@example.com');
        $order->setPhone('+380501111111');
        $order->setName('Test Customer');
        $order->setSurname('Surname');
        $order->setStatus(Order::NEW);
        $order->setPaymentStatus(false);
        $order->setTotal(1000);

        return $order;
    }

    /**
     * Create test order without email
     */
    private function createOrderWithoutEmail(): Order
    {
        $order = new Order();
        $order->setOrderNumber('TEST-456');
        $order->setPhone('+380502222222');
        $order->setName('Test Customer');
        $order->setSurname('Surname');
        $order->setStatus(Order::NEW);
        $order->setPaymentStatus(false);
        $order->setTotal(1000);

        return $order;
    }

    /**
     * Setup setting repository mock
     */
    private function setupSettingRepositoryMock(): void
    {
        $this->settingRepository->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if ($criteria['slug'] === 'email') {
                    return $this->emailSetting;
                }
                if ($criteria['slug'] === 'phone_number') {
                    return $this->phoneSetting;
                }
                return null;
            });
    }

    /**
     * Setup twig mock with default rendering
     */
    private function setupTwigMock(): void
    {
        $this->twig->method('render')
            ->willReturn('<html>Test Email</html>');
    }

    // ========================================
    // Custom Matchers (Best Practice)
    // ========================================

    /**
     * Custom matcher: Check email from address
     * @return Callback<Email>
     */
    private function emailFromAddress(string $expectedAddress): Callback
    {
        return $this->callback(function (Email $email) use ($expectedAddress) {
            $from = $email->getFrom();
            if (empty($from)) {
                return false;
            }

            $fromAddress = $from[0]->getAddress();
            return $fromAddress === $expectedAddress;
        });
    }
}
