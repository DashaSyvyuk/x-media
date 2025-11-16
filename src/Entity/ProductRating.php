<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\ProductRatingRepository")]
#[ORM\HasLifecycleCallbacks()]
#[ORM\Table(name: "product_rating")]
class ProductRating
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: "App\Entity\Product", inversedBy: "characteristics")]
    private Product $product;

    #[ORM\Column(type: "decimal", precision: 8, scale: 2)]
    private string $value;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
