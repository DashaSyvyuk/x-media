<?php

namespace App\Entity;

use App\Repository\InStockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: "in_stock",
    indexes: [
        new ORM\Index(columns: ["quantity"]),
        new ORM\Index(columns: ["purchase_price"]),
        new ORM\Index(columns: ["product_id"]),
        new ORM\Index(columns: ["warehouse_id"])
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(columns: ["product_id", "warehouse_id"])
    ]
)]
#[ORM\Entity(repositoryClass: InStockRepository::class)]
class InStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\JoinColumn(name: "product_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Product::class)]
    private Product $product;

    #[ORM\JoinColumn(name: "warehouse_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Warehouse::class)]
    private Warehouse $warehouse;

    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $quantity = 0;

    #[ORM\Column(type: "integer", nullable: true, options: ["unsigned" => true])]
    private ?int $purchasePrice = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $note = null;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

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
        $p = method_exists($this->product ?? null, 'getTitle') ?
            $this->product->getTitle() :
            (string) ($this->product->getId());
        $w = method_exists($this->warehouse ?? null, 'getTitle') ?
            $this->warehouse->getTitle() :
            (string) ($this->warehouse->getId());
        return sprintf('%s @ %s (%d)', $p, $w, $this->quantity);
    }
}
