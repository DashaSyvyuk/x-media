<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("supplier_order_product")
 * @ORM\Entity(repositoryClass="App\Repository\SupplierOrderProductRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SupplierOrderProduct
{
    use DateStorageTrait;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\SupplierOrder")
     */
    private SupplierOrder $supplierOrder;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\SupplierProduct")
     */
    private SupplierProduct $product;

    /**
     * @ORM\Column(type="integer")
     */
    private int $quantity = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $price = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $updatedAt;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param SupplierOrder $supplierOrder
     */
    public function setSupplierOrder(SupplierOrder $supplierOrder): void
    {
        $this->supplierOrder = $supplierOrder;
    }

    /**
     * @return SupplierOrder
     */
    public function getSupplierOrder(): SupplierOrder
    {
        return $this->supplierOrder;
    }

    /**
     * @param SupplierProduct $product
     */
    public function setProduct(SupplierProduct $product): void
    {
        $this->product = $product;
    }

    /**
     * @return SupplierProduct
     */
    public function getProduct(): SupplierProduct
    {
        return $this->product;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
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

    public function getSupplierProductPrice(): ?int
    {
        if (!$this->product) {
            return null;
        }

        return $this->product->getPrice();
    }

    public function getProductPrice(): ?int
    {
        if (!$this->product) {
            return null;
        }

        return $this->product->getProduct()->getPrice();
    }
}
