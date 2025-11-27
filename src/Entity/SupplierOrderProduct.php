<?php

namespace App\Entity;

use App\Repository\SupplierOrderProductRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("supplier_order_product")]
#[ORM\Entity(repositoryClass: SupplierOrderProductRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class SupplierOrderProduct
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: SupplierOrder::class)]
    private SupplierOrder $supplierOrder;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: SupplierProduct::class)]
    private SupplierProduct $product;

    #[ORM\Column(type: "integer")]
    private int $quantity = 0;

    #[ORM\Column(type: "integer")]
    private int $price = 0;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setSupplierOrder(SupplierOrder $supplierOrder): void
    {
        $this->supplierOrder = $supplierOrder;
    }

    public function getSupplierOrder(): SupplierOrder
    {
        return $this->supplierOrder;
    }

    public function setProduct(SupplierProduct $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): SupplierProduct
    {
        return $this->product;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getSupplierProductPrice(): ?int
    {
        return $this->product->getPrice();
    }

    public function getProductPrice(): ?int
    {
        return $this->product->getProduct()->getPrice();
    }
}
