<?php

namespace App\Entity;

use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table("rozetka_product")]
#[ORM\Entity(repositoryClass: "App\Repository\RozetkaProductRepository")]
#[ORM\HasLifecycleCallbacks()]
class RozetkaProduct
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\Column(type: "integer")]
    private int $stockQuantity = 0;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $series = "";

    #[ORM\Column(type: "text")]
    private string $description = "";

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\GreaterThanOrEqual(value: "1", message: "Too low value")]
    private ?int $price = 0;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\GreaterThanOrEqual(value: "1", message: "Too low value")]
    private ?int $promoPrice = 0;

    #[ORM\Column(type: "boolean")]
    private bool $promoPriceActive = false;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\GreaterThan(propertyPath: "price", message: "Too low value")]
    private ?int $crossedOutPrice = 0;

    #[ORM\OneToMany(
        targetEntity: "App\Entity\ProductRozetkaCharacteristicValue",
        mappedBy: "rozetkaProduct",
        cascade: ["all"],
        orphanRemoval: true
    )]
    private $values;

    #[ORM\OneToOne(targetEntity: "App\Entity\Product")]
    #[ORM\JoinColumn(name: "product_id", referencedColumnName: "id")]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: "App\Entity\RozetkaProduct")]
    #[ORM\JoinColumn(name: "rozetka_product_id", referencedColumnName: "id")]
    private ?RozetkaProduct $rozetkaProduct = null;

    #[ORM\Column(type: "boolean")]
    private bool $ready = false;

    #[ORM\Column(type: "boolean")]
    private bool $activeForA = false;

    #[ORM\Column(type: "boolean")]
    private bool $activeForP = false;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): void
    {
        $this->stockQuantity = $stockQuantity;
    }

    public function getSeries(): ?string
    {
        return $this->series;
    }

    public function setSeries(?string $series): void
    {
        $this->series = $series;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): void
    {
        $this->price = $price;
    }

    public function getPromoPrice(): ?int
    {
        return $this->promoPrice;
    }

    public function setPromoPrice(?int $promoPrice): void
    {
        $this->promoPrice = $promoPrice;
    }

    public function getCrossedOutPrice(): ?int
    {
        return $this->crossedOutPrice;
    }

    public function setCrossedOutPrice(?int $crossedOutPrice): void
    {
        $this->crossedOutPrice = $crossedOutPrice;
    }

    public function getValues()
    {
        return $this->values->filter(function ($value) {
            $characteristic = $value->getCharacteristic();
            return $characteristic && $characteristic->getActive();
        });
    }

    public function setValues($values): void
    {
        if (count($values) > 0) {
            foreach ($values as $value) {
                $this->addValue($value);
            }
        }
    }

    /**
     * @param ProductRozetkaCharacteristicValue $value
     */
    public function addValue(ProductRozetkaCharacteristicValue $value): void
    {
        $value->setRozetkaProduct($this);
        $this->values[] = $value;
    }

    /**
     * @param ProductRozetkaCharacteristicValue $value
     */
    public function removeValue(ProductRozetkaCharacteristicValue $value): void
    {
        if ($this->values->contains($value)) {
            $this->values->removeElement($value);
        }
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getRozetkaProduct(): ?RozetkaProduct
    {
        return $this->rozetkaProduct;
    }

    public function setRozetkaProduct(?RozetkaProduct $rozetkaProduct): void
    {
        $this->rozetkaProduct = $rozetkaProduct;
    }

    public function getReady(): bool
    {
        return $this->ready;
    }

    public function setReady(bool $ready): void
    {
        $this->ready = $ready;
    }

    public function getPromoPriceActive(): bool
    {
        return $this->promoPriceActive;
    }

    public function setPromoPriceActive(bool $promoPriceActive): void
    {
        $this->promoPriceActive = $promoPriceActive;
    }

    public function getActiveForA(): bool
    {
        return $this->activeForA;
    }

    public function setActiveForA(bool $activeForA): void
    {
        $this->activeForA = $activeForA;
    }

    public function getActiveForP(): bool
    {
        return $this->activeForP;
    }

    public function setActiveForP(bool $activeForP): void
    {
        $this->activeForP = $activeForP;
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

    public function __toString(): string
    {
        return $this->product->getId() . ' - ' . $this->title;
    }
}
