<?php

namespace App\Tests\Unit\Service\Comment;

use App\Entity\Comment;
use App\Entity\Product;
use App\Entity\ProductRating;
use App\Repository\CommentRepository;
use App\Repository\ProductRatingRepository;
use App\Repository\ProductRepository;
use App\Service\Comment\CommentCreateService;
use PHPUnit\Framework\TestCase;

/**
 * Test for Comment CreateService
 *
 * @group unit
 * @group service
 * @group comment
 * @group user-content
 */
class CommentCreateServiceTest extends TestCase
{
    private const AUTHOR = 'John Doe';
    private const EMAIL = 'john@example.com';
    private const COMMENT = 'Great product!';
    private CommentCreateService $service;
    /** @var CommentRepository&\PHPUnit\Framework\MockObject\MockObject */
    private CommentRepository $commentRepository;
    /** @var ProductRatingRepository&\PHPUnit\Framework\MockObject\MockObject */
    private ProductRatingRepository $productRatingRepository;
    /** @var ProductRepository&\PHPUnit\Framework\MockObject\MockObject */
    private ProductRepository $productRepository;
    private Product $testProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commentRepository = $this->createMock(CommentRepository::class);
        $this->productRatingRepository = $this->createMock(ProductRatingRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->testProduct = new Product();
        $this->testProduct->setId(1);
        $this->testProduct->setTitle('Test Product');
        $this->testProduct->setStatus(Product::STATUS_ACTIVE);
        $this->testProduct->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $this->testProduct->setPrice(1000);
        $this->testProduct->setProductCode('TEST-001');

        $this->service = new CommentCreateService(
            $this->commentRepository,
            $this->productRatingRepository,
            $this->productRepository
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->service, $this->commentRepository, $this->productRatingRepository, $this->productRepository);
    }

    /**
     * Test service creates comment with all required data
     *
     * @group critical
     */
    public function testRunCreatesCommentWithAllData(): void
    {
        $data = $this->getValidCommentData();
        $productRating = $this->createProductRating();

        $this->setupProductRepositoryMock();
        $this->setupProductRatingRepositoryMock($productRating);

        $this->commentRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($commentData) {
                return $commentData['author'] === self::AUTHOR
                    && $commentData['email'] === self::EMAIL
                    && $commentData['comment'] === self::COMMENT
                    && $commentData['status'] === Comment::STATUS_NEW
                    && isset($commentData['product'])
                    && isset($commentData['productRating']);
            }))
            ->willReturn($this->createComment());

        $result = $this->service->run($data);

        $this->assertInstanceOf(Comment::class, $result);
    }

    /**
     * Test service creates product rating first
     *
     * @group rating
     */
    public function testRunCreatesProductRatingBeforeComment(): void
    {
        $data = $this->getValidCommentData();
        $productRating = $this->createProductRating();

        $this->setupProductRepositoryMock();

        $this->productRatingRepository->expects($this->once())
            ->method('create')
            ->with($this->logicalAnd(
                $this->arrayHasKey('product'),
                $this->arrayHasKey('rating')
            ))
            ->willReturn($productRating);

        $this->commentRepository->method('create')
            ->willReturn($this->createComment());

        $this->service->run($data);
    }

    /**
     * Test service finds product by ID
     *
     * @group product
     */
    public function testRunFindsProductById(): void
    {
        $data = $this->getValidCommentData();
        $productId = 42;
        $data['product'] = $productId;

        $this->productRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['id' => $productId]))
            ->willReturn($this->testProduct);

        $this->productRatingRepository->method('create')
            ->willReturn($this->createProductRating());

        $this->commentRepository->method('create')
            ->willReturn($this->createComment());

        $this->service->run($data);
    }

    /**
     * Test service passes correct rating value
     *
     * @group rating
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('ratingProvider')]
    public function testRunPassesCorrectRatingValue(int $rating): void
    {
        $data = $this->getValidCommentData();
        $data['rating'] = $rating;

        $this->setupProductRepositoryMock();

        $this->productRatingRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($ratingData) use ($rating) {
                return isset($ratingData['rating']) && $ratingData['rating'] === $rating;
            }))
            ->willReturn($this->createProductRating());

        $this->commentRepository->method('create')
            ->willReturn($this->createComment());

        $this->service->run($data);
    }

    /**
     * Data provider for rating values
     * @return array<string, array{int}>
     */
    public static function ratingProvider(): array
    {
        return [
            'one star' => [1],
            'three stars' => [3],
            'five stars' => [5],
        ];
    }

    /**
     * Test comment is created with NEW status
     *
     * @group status
     */
    public function testCommentIsCreatedWithNewStatus(): void
    {
        $data = $this->getValidCommentData();

        $this->setupProductRepositoryMock();
        $this->setupProductRatingRepositoryMock();

        $this->commentRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($commentData) {
                return $commentData['status'] === Comment::STATUS_NEW;
            }))
            ->willReturn($this->createComment());

        $this->service->run($data);
    }

    /**
     * Test service returns created comment
     *
     * @group return-value
     */
    public function testRunReturnsCreatedComment(): void
    {
        $data = $this->getValidCommentData();
        $expectedComment = $this->createComment();

        $this->setupProductRepositoryMock();
        $this->setupProductRatingRepositoryMock();

        $this->commentRepository->method('create')
            ->willReturn($expectedComment);

        $result = $this->service->run($data);

        $this->assertSame($expectedComment, $result);
    }

    /**
     * Test service handles multiple comments for same product
     *
     * @group concurrency
     */
    public function testServiceHandlesMultipleCommentsForSameProduct(): void
    {
        $data1 = $this->getValidCommentData();
        $data2 = $this->getValidCommentData();
        $data2['author'] = 'Jane Doe';
        $data2['email'] = 'jane@example.com';

        $this->setupProductRepositoryMock();
        $this->setupProductRatingRepositoryMock();

        $this->commentRepository->method('create')
            ->willReturnOnConsecutiveCalls(
                $this->createComment(),
                $this->createComment()
            );

        $result1 = $this->service->run($data1);
        $result2 = $this->service->run($data2);

        $this->assertInstanceOf(Comment::class, $result1);
        $this->assertInstanceOf(Comment::class, $result2);
    }

    /**
     * Helper method to get valid comment data
     * @return array<string, mixed>
     */
    private function getValidCommentData(): array
    {
        return [
            'product' => 1,
            'author' => self::AUTHOR,
            'email' => self::EMAIL,
            'comment' => self::COMMENT,
            'rating' => 5,
        ];
    }

    private function createComment(): Comment
    {
        $comment = new Comment();
        $comment->setAuthor(self::AUTHOR);
        $comment->setEmail(self::EMAIL);
        $comment->setComment(self::COMMENT);
        $comment->setStatus(Comment::STATUS_NEW);
        $comment->setProduct($this->testProduct);
        return $comment;
    }

    private function createProductRating(): ProductRating
    {
        $rating = new ProductRating();
        $rating->setValue('5');
        $rating->setProduct($this->testProduct);
        return $rating;
    }

    private function setupProductRepositoryMock(): void
    {
        $this->productRepository->method('findOneBy')
            ->willReturn($this->testProduct);
    }

    private function setupProductRatingRepositoryMock(?ProductRating $rating = null): void
    {
        $this->productRatingRepository->method('create')
            ->willReturn($rating ?? $this->createProductRating());
    }
}
