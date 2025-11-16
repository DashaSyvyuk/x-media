<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("supplier_product")]
#[ORM\Entity(repositoryClass: "App\Repository\SupplierProductRepository")]
#[ORM\HasLifecycleCallbacks()]
class SupplierProduct
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "App\Entity\Product")]
    private ?Product $product = null;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "App\Entity\Supplier", inversedBy: "products")]
    private ?Supplier $supplier = null;

    #[ORM\Column(type: "integer")]
    private int $price = 0;

    #[ORM\Column(type: "string")]
    private string $spending = "";

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param Product|null $product
     */
    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Supplier|null $supplier
     */
    public function setSupplier(?Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    /**
     * @return Supplier|null
     */
    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    /**
     * @param int $price
     */
    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @param string|null $spending
     */
    public function setSpending(?string $spending): void
    {
        $this->spending = $spending;
    }

    /**
     * @return string|null
     */
    public function getSpending(): ?string
    {
        return $this->spending;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString(): string
    {
        return (string) $this->product->getTitle();
    }
}
