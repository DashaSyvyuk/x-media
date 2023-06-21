<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("supplier_product")
 * @ORM\Entity(repositoryClass="App\Repository\SupplierProductRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SupplierProduct
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
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     */
    private Product $product;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Supplier")
     */
    private Supplier $supplier;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private int $price = 0;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private int $spending = 0;

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
     * @param Supplier $supplier
     */
    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    /**
     * @return Supplier
     */
    public function getSupplier(): string
    {
        return $this->supplier;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
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
     * @param int $spending
     */
    public function setSpending(int $spending): void
    {
        $this->spending = $spending;
    }

    /**
     * @return int
     */
    public function getSpending(): int
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
        return (string) $this->getProduct()->getTitle();
    }
}
