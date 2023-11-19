<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("product_filter_attribute", indexes={
 *     @Index(columns={"product_id", "filter_id", "filter_attribute_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ProductFilterAttributeRepository")
 */
class ProductFilterAttribute
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private ?int $id = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="filterAttributes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Filter::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $filter;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity=FilterAttribute::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $filterAttribute;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(?int $id): void
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

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }

    public function setFilter(Filter $filter): void
    {
        $this->filter = $filter;
    }

    public function getFilterAttribute(): ?FilterAttribute
    {
        return $this->filterAttribute;
    }

    public function setFilterAttribute(FilterAttribute $filterAttribute): void
    {
        $this->filterAttribute = $filterAttribute;
    }
}
