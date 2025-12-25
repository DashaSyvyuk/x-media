<?php

namespace App\Entity;

use App\Repository\PromotionProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("promotion_product")]
#[ORM\Entity(repositoryClass: PromotionProductRepository::class)]
class PromotionProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "promotionProducts")]
    private Product $product;

    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Promotion::class, inversedBy: "products")]
    private Promotion $promotion;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotion $promotion): void
    {
        $this->promotion = $promotion;
    }
}
