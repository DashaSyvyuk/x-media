<?php

namespace App\Entity;

use App\Repository\ProductCharacteristicRepository;
use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('product_characteristic', indexes: [
    new ORM\Index(columns: ["product_id", "position"])
])]
#[ORM\Entity(repositoryClass: ProductCharacteristicRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class ProductCharacteristic
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private ?int $id = 0;

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\Column(type: "text")]
    private string $value = "";

    #[ORM\Column(type: "integer", nullable: true, options: ["unsigned" => true])]
    private ?int $position = 0;

    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "characteristics")]
    private Product $product;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private DateTime $updatedAt;

    public function getId(): ?int
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
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
        $title = trim($this->title);
        $value = trim($this->value);

        if ($title === '' && $value === '') {
            return '';
        }
        if ($title === '') {
            return $value;
        }
        if ($value === '') {
            return $title;
        }

        return $title . ': ' . $value;
    }
}
