<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Table(
 *     name="in_stock",
 *     indexes={
 *         @Index(columns={"quantity"}),
 *         @Index(columns={"purchase_price"}),
 *         @Index(columns={"product_id"}),
 *         @Index(columns={"warehouse_id"})
 *     },
 *     uniqueConstraints={
 *         @UniqueConstraint(columns={"product_id", "warehouse_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\InStockRepository")
 */
class InStock
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     */
    private Product $product;

    /**
     * @ORM\JoinColumn(name="warehouse_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Warehouse")
     */
    private Warehouse $warehouse;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $quantity = 0;

    /**
     * @var int|null
     * @ORM\Column(type="integer", options={"unsigned"=true}, nullable=true)
     */
    private ?int $purchasePrice = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $note = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setWarehouse(Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setPurchasePrice(?int $purchasePrice): void
    {
        $this->purchasePrice = $purchasePrice;
    }

    public function getPurchasePrice(): ?int
    {
        return $this->purchasePrice;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function __toString(): string
    {
        $p = method_exists($this->product ?? null, 'getTitle') ? $this->product->getTitle() : (string)($this->product->getId() ?? '');
        $w = method_exists($this->warehouse ?? null, 'getTitle') ? $this->warehouse->getTitle() : (string)($this->warehouse->getId() ?? '');
        return sprintf('%s @ %s (%d)', $p, $w, $this->quantity);
    }
}