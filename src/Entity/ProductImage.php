<?php

namespace App\Entity;

use App\Repository\ProductImageRepository;
use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Table('product_image', indexes: [
    new ORM\Index(columns: ["product_id", "position"])
])]
#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
#[ORM\HasLifecycleCallbacks()]
#[Vich\Uploadable]
class ProductImage
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private ?int $id = null;

    #[ORM\Column(type: "text")]
    private ?string $imageUrl;

    #[Vich\UploadableField(mapping: "images", fileNameProperty: "imageUrl")]
    private ?File $file = null;

    #[ORM\Column(type: "integer", nullable: true, options: ["unsigned" => true])]
    private ?int $position = 0;

    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "images")]
    private Product $product;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): int
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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function setFile(?File $file): void
    {
        $this->file = $file;
        if ($file) {
            $this->updatedAt = new DateTime();
        }
    }

    public function getFile(): ?File
    {
        return $this->file;
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

    public function __toString()
    {
        return $this->imageUrl;
    }
}
