<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("promotion_product")
 * @ORM\Entity(repositoryClass="App\Repository\PromotionProductRepository")
 */
class PromotionProduct
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     */
    private Product $product;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Promotion", inversedBy="products")
     */
    private Promotion $promotion;

    public function getId(): int
    {
        return $this->id;
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
