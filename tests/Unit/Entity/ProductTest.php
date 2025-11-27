<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Product;
use App\Entity\ProductCharacteristic;
use App\Entity\ProductImage;
use App\Entity\ProductRating;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group entity
 */
class ProductTest extends TestCase
{
    public function testProductCreationWithDefaultValues(): void
    {
        $product = new Product();
        
        $this->assertSame("", $product->getStatus());
        $this->assertSame("", $product->getAvailability());
        $this->assertSame(0, $product->getPrice());
        $this->assertCount(0, $product->getImages());
        $this->assertCount(0, $product->getCharacteristics());
        $this->assertCount(0, $product->getComments());
    }

    public function testProductSettersAndGetters(): void
    {
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setDescription('Test Description');
        $product->setStatus(Product::STATUS_ACTIVE);
        $product->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $product->setPrice(1500);
        $product->setCrossedOutPrice(2000);
        $product->setProductCode('PROD-001');
        
        $this->assertSame('Test Product', $product->getTitle());
        $this->assertSame('Test Description', $product->getDescription());
        $this->assertSame(Product::STATUS_ACTIVE, $product->getStatus());
        $this->assertSame(Product::AVAILABILITY_AVAILABLE, $product->getAvailability());
        $this->assertSame(1500, $product->getPrice());
        $this->assertSame(2000, $product->getCrossedOutPrice());
        $this->assertSame('PROD-001', $product->getProductCode());
    }

    public function testProductStatusConstants(): void
    {
        $this->assertSame('Активний', Product::STATUS_ACTIVE);
        $this->assertSame('Заблокований', Product::STATUS_BLOCKED);
    }

    public function testProductAvailabilityConstants(): void
    {
        $this->assertSame('в наявності', Product::AVAILABILITY_AVAILABLE);
        $this->assertSame('під замовлення (1-3 дні)', Product::AVAILABILITY_TO_ORDER);
        $this->assertSame('передзамовлення', Product::AVAILABILITY_PRE_ORDER);
        
        $availabilities = Product::AVAILABILITIES;
        $this->assertIsArray($availabilities);
        $this->assertCount(3, $availabilities);
    }

    public function testProductImageManagement(): void
    {
        $product = new Product();
        $image1 = new ProductImage();
        $image2 = new ProductImage();
        
        $product->addImage($image1);
        $this->assertCount(1, $product->getImages());
        
        $product->addImage($image2);
        $this->assertCount(2, $product->getImages());
        
        $product->removeImage($image1);
        $this->assertCount(1, $product->getImages());
    }

    public function testProductCharacteristicManagement(): void
    {
        $product = new Product();
        $characteristic1 = new ProductCharacteristic();
        $characteristic2 = new ProductCharacteristic();
        
        $product->addCharacteristic($characteristic1);
        $this->assertCount(1, $product->getCharacteristics());
        $this->assertSame($product, $characteristic1->getProduct());
        
        $product->addCharacteristic($characteristic2);
        $this->assertCount(2, $product->getCharacteristics());
        
        $product->removeCharacteristic($characteristic1);
        $this->assertCount(1, $product->getCharacteristics());
    }

    public function testProductCommentManagement(): void
    {
        $product = new Product();
        $comment1 = new Comment();
        $comment2 = new Comment();
        
        $product->addComment($comment1);
        $this->assertCount(1, $product->getComments());
        
        $product->addComment($comment2);
        $this->assertCount(2, $product->getComments());
        
        $product->removeComment($comment1);
        $this->assertCount(1, $product->getComments());
    }

    public function testProductRatingManagement(): void
    {
        $product = new Product();
        $rating1 = new ProductRating();
        $rating2 = new ProductRating();
        
        $product->addRating($rating1);
        $this->assertCount(1, $product->getRatings());
        
        $product->addRating($rating2);
        $this->assertCount(2, $product->getRatings());
        
        $product->removeRating($rating1);
        $this->assertCount(1, $product->getRatings());
    }

    public function testProductAverageRatingWithNoRatings(): void
    {
        $product = new Product();
        
        $this->assertSame(0, $product->getAverageRating());
    }

    public function testProductAverageRatingWithRatings(): void
    {
        $product = new Product();
        
        $rating1 = new ProductRating();
        $rating1->setValue(5);
        $product->addRating($rating1);
        
        $rating2 = new ProductRating();
        $rating2->setValue(3);
        $product->addRating($rating2);
        
        $rating3 = new ProductRating();
        $rating3->setValue(4);
        $product->addRating($rating3);
        
        $expectedAverage = (5 + 3 + 4) / 3;
        $this->assertEquals($expectedAverage, $product->getAverageRating());
    }

    public function testProductCategoryRelationship(): void
    {
        $product = new Product();
        $category = new Category();
        
        $product->setCategory($category);
        $this->assertSame($category, $product->getCategory());
    }

    public function testProductDateFields(): void
    {
        $product = new Product();
        $createdAt = new DateTime('2024-01-15 10:30:00');
        $updatedAt = new DateTime('2024-01-15 11:30:00');
        
        $product->setCreatedAt($createdAt);
        $product->setUpdatedAt($updatedAt);
        
        $this->assertSame($createdAt, $product->getCreatedAt());
        $this->assertSame($updatedAt, $product->getUpdatedAt());
    }

    public function testProductToString(): void
    {
        $product = new Product();
        $product->setId(123);
        $product->setTitle('Test Product');
        
        $this->assertSame('123 - Test Product', (string) $product);
    }
}

