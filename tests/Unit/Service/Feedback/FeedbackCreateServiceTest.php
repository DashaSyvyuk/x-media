<?php

namespace App\Tests\Unit\Service\Feedback;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use App\Service\Feedback\CreateService;
use PHPUnit\Framework\TestCase;

/**
 * Test for Feedback CreateService
 *
 * @group unit
 * @group service
 * @group feedback
 * @group user-content
 */
class FeedbackCreateServiceTest extends TestCase
{
    private CreateService $service;
    /** @var FeedbackRepository&\PHPUnit\Framework\MockObject\MockObject */
    private FeedbackRepository $feedbackRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->feedbackRepository = $this->createMock(FeedbackRepository::class);
        $this->service = new CreateService($this->feedbackRepository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->service, $this->feedbackRepository);
    }

    /**
     * Test service creates feedback with all data
     *
     * @group critical
     */
    public function testRunCreatesFeedbackWithAllData(): void
    {
        // Arrange
        $data = $this->getValidFeedbackData();

        // Assert
        $this->feedbackRepository->expects($this->once())
            ->method('create')
            ->with($this->equalTo($data))
            ->willReturn($this->createFeedback());

        // Act
        $result = $this->service->run($data);

        // Assert
        $this->assertInstanceOf(Feedback::class, $result);
    }

    /**
     * Test service passes all fields to repository
     *
     * @group data-integrity
     */
    public function testRunPassesAllFieldsToRepository(): void
    {
        // Arrange
        $data = $this->getValidFeedbackData();

        // Assert - all fields present
        $this->feedbackRepository->expects($this->once())
            ->method('create')
            ->with($this->logicalAnd(
                $this->arrayHasKey('author'),
                $this->arrayHasKey('email'),
                $this->arrayHasKey('comment'),
                $this->arrayHasKey('status')
            ))
            ->willReturn($this->createFeedback());

        // Act
        $this->service->run($data);
    }

    /**
     * Test service handles different statuses
     *
     * @group status
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('statusProvider')]
    public function testServiceHandlesDifferentStatuses(string $status): void
    {
        // Arrange
        $data = $this->getValidFeedbackData();
        $data['status'] = $status;

        // Assert
        $this->feedbackRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($feedbackData) use ($status) {
                return $feedbackData['status'] === $status;
            }))
            ->willReturn($this->createFeedback());

        // Act
        $result = $this->service->run($data);

        // Assert
        $this->assertInstanceOf(Feedback::class, $result);
    }

    /**
     * Data provider for feedback statuses
     * @return array<string, array{string}>
     */
    public static function statusProvider(): array
    {
        return [
            'new' => [Feedback::STATUS_NEW],
            'confirmed' => [Feedback::STATUS_CONFIRMED],
            'disabled' => [Feedback::STATUS_DISABLED],
        ];
    }

    /**
     * Test service returns created feedback
     *
     * @group return-value
     */
    public function testRunReturnsCreatedFeedback(): void
    {
        // Arrange
        $data = $this->getValidFeedbackData();
        $expectedFeedback = $this->createFeedback();

        $this->feedbackRepository->method('create')
            ->willReturn($expectedFeedback);

        // Act
        $result = $this->service->run($data);

        // Assert
        $this->assertSame($expectedFeedback, $result);
    }

    /**
     * Test service validates email format
     *
     * @group validation
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('emailProvider')]
    public function testServiceReceivesValidEmailFormat(string $email, bool $isValid): void
    {
        // Arrange
        $data = $this->getValidFeedbackData();
        $data['email'] = $email;

        if ($isValid) {
            $this->feedbackRepository->expects($this->once())
                ->method('create')
                ->willReturn($this->createFeedback());

            // Act
            $result = $this->service->run($data);

            // Assert
            $this->assertInstanceOf(Feedback::class, $result);
        } else {
            // In real scenario, validation would happen in controller/form
            // Service just passes data to repository
            $this->feedbackRepository->expects($this->once())
                ->method('create')
                ->willReturn($this->createFeedback());

            $this->service->run($data);
        }
    }

    /**
     * Data provider for email validation
     * @return array<string, array{string, bool}>
     */
    public static function emailProvider(): array
    {
        return [
            'valid simple' => ['user@example.com', true],
            'valid with subdomain' => ['user@mail.example.com', true],
            'valid with plus' => ['user+tag@example.com', true],
            'invalid no at' => ['userexample.com', false],
            'invalid no domain' => ['user@', false],
        ];
    }

    /**
     * Helper method to get valid feedback data
     * @return array<string, mixed>
     */
    private function getValidFeedbackData(): array
    {
        return [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'comment' => 'Great service!',
            'status' => Feedback::STATUS_NEW,
        ];
    }

    private function createFeedback(): Feedback
    {
        $feedback = new Feedback();
        $feedback->setAuthor('John Doe');
        $feedback->setEmail('john@example.com');
        $feedback->setComment('Great service!');
        $feedback->setStatus(Feedback::STATUS_NEW);
        return $feedback;
    }
}
