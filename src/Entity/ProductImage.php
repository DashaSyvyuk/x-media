<?php

namespace App\Entity;

use \DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Table("product_image", indexes={
 *     @Index(columns={"product_id", "position"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ProductImageRepository")
 * @Vich\Uploadable()
 */
class ProductImage
{
    use DateStorageTrait;

    const SERVER_PATH_TO_IMAGE_FOLDER = '../public/images/products';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="text")
     */
    private $imageUrl;

    /**
     * @Vich\UploadableField(mapping="images", fileNameProperty="imageUrl")
     */
    private $file;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $position = 0;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="images")
     */
    private Product $product;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @param $imageUrl
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
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

    public function setFile($file)
    {
        $this->file = $file;
        if ($file) {
            $this->updatedAt = new DateTime();
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function __toString()
    {
        return $this->imageUrl;
    }
}
